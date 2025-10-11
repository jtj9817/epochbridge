<?php

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use App\Events\TestExecutionStarted;
use App\Services\ProgressTrackingService;
use App\Services\TestDiscoveryService;
use App\Services\TestExecutionService;
use Illuminate\Support\Facades\Event;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SelectPrompt;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;

uses(TestCase::class);

beforeAll(function () {
    Prompt::fallbackWhen(true);

    SelectPrompt::fallbackUsing(function (SelectPrompt $prompt) {
        return PromptSequence::pull($prompt->default ?? (array_is_list($prompt->options) ? $prompt->options[0] : array_key_first($prompt->options)));
    });

    MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) {
        $value = PromptSequence::pull($prompt->default ?? []);

        return is_array($value) ? $value : [$value];
    });

    ConfirmPrompt::fallbackUsing(function (ConfirmPrompt $prompt) {
        return (bool) PromptSequence::pull($prompt->default ?? true);
    });
});

beforeEach(function () {
    PromptSequence::reset();
    Event::fake();
});

afterEach(function () {
    m::close();
});

it('displays menu options and cancels gracefully when user declines execution', function () {
    $stats = [
        'total' => 6,
        'unit' => 2,
        'feature' => 2,
        'livewire' => 1,
        'browser' => 1,
    ];

    $discovery = $this->partialMock(TestDiscoveryService::class, function (Mockery\MockInterface $mock) use ($stats) {
        $mock->shouldReceive('getTestStatistics')->andReturn($stats);
    });

    $execution = $this->mock(TestExecutionService::class);
    $progress = $this->mock(ProgressTrackingService::class);

    $execution->shouldReceive('startTestExecution')->never();

    PromptSequence::seed(['all', false]);

    $this->artisan('test:runner')
        ->expectsOutputToContain('🌍 All Tests - Run the complete test suite')
        ->expectsOutputToContain('🎭 Feature Tests - Test complete user workflows')
        ->expectsOutputToContain('Test execution cancelled by user.')
        ->assertExitCode(0);
});

it('launches unit tests and polls progress until completion', function () {
    $dataset = mockDiscoveryDataset();

    $execution = $this->mock(TestExecutionService::class);
    $progress = $this->mock(ProgressTrackingService::class);

    $execution->shouldReceive('startTestExecution')
        ->once()
        ->withArgs(function ($files, $output, $options) use ($dataset) {
            return count($files) === count($dataset['unit'])
                && $output === 'test-report.md'
                && $options['parallel'] === true
                && $options['processes'] === 4;
        })
        ->andReturnUsing(function () {
            TestExecutionStarted::dispatch('command-unit-001', 3, now(), []);
            TestCompleted::dispatch('tests/Unit/FooTest.php', 'passed', 1.1, '', '', []);
            TestExecutionCompleted::dispatch('command-unit-001', ['status' => 'passed'], now());

            return 'command-unit-001';
        });

    $execution->shouldReceive('trackProgress')
        ->andReturn([
            'status' => 'completed',
            'completed_tests' => 3,
            'total_tests' => 3,
        ]);

    $execution->shouldReceive('getExecutionReport')
        ->andReturn([
            'summary' => [
                'status' => 'passed',
                'total_tests' => 3,
                'total_passed' => 3,
                'total_failed' => 0,
                'total_skipped' => 0,
                'total_duration' => 4.2,
                'success_rate' => 100,
            ],
            'output_file' => 'test-report.md',
        ]);

    $progress->shouldReceive('determineTestType')->andReturn('unit');
    $progress->shouldReceive('setActiveCommand')->once();
    $progress->shouldReceive('registerOutput')->atLeast()->once();
    $progress->shouldReceive('displayCurrentStatus')->andReturnNull();
    $progress->shouldReceive('getStatistics')->andReturn([
        'completed' => 3,
        'passed' => 3,
        'failed' => 0,
        'skipped' => 0,
        'average_duration' => 1.4,
        'summary' => [
            'status' => 'passed',
            'total_tests' => 3,
            'total_passed' => 3,
            'total_failed' => 0,
            'total_skipped' => 0,
            'total_duration' => 4.2,
            'success_rate' => 100,
        ],
    ]);

    PromptSequence::seed([
        'unit',        // main menu
        true,          // proceed execution
        true,          // parallel?
        '4',           // processes
        false,         // stop on failure
        false,         // coverage
        '10',          // timeout minutes
    ]);

    $this->artisan('test:runner')
        ->expectsOutputToContain('🚀 Starting Test Execution')
        ->expectsOutputToContain('Test Execution Completed Successfully')
        ->assertExitCode(0);

    Event::assertDispatched(TestExecutionStarted::class);
    Event::assertDispatched(TestExecutionCompleted::class);
    Event::assertDispatched(TestCompleted::class);
});

it('supports custom selection via category flow', function () {
    $dataset = mockDiscoveryDataset();

    $execution = $this->mock(TestExecutionService::class);
    $progress = $this->mock(ProgressTrackingService::class);

    $execution->shouldReceive('startTestExecution')
        ->once()
        ->withArgs(function ($files, $output) use ($dataset) {
            return count($files) === count($dataset['unit']) + count($dataset['feature'])
                && $output === 'test-report.md';
        })
        ->andReturnUsing(function () {
            TestExecutionStarted::dispatch('cmd-custom-1', 2, now(), []);
            TestCompleted::dispatch('tests/Feature/AlphaTest.php', 'failed', 1.5, '', '', []);
            TestExecutionCompleted::dispatch('cmd-custom-1', ['status' => 'failed'], now());

            return 'cmd-custom-1';
        });

    $execution->shouldReceive('trackProgress')
        ->andReturn([
            'status' => 'completed',
            'completed_tests' => 2,
            'total_tests' => 2,
        ]);

    $execution->shouldReceive('getExecutionReport')->andReturn([
        'summary' => [
            'status' => 'failed',
            'total_tests' => 2,
            'total_passed' => 1,
            'total_failed' => 1,
            'total_skipped' => 0,
            'total_duration' => 3.1,
            'success_rate' => 50,
        ],
        'output_file' => 'test-report.md',
    ]);

    $progress->shouldReceive('determineTestType')->andReturnUsing(function ($path) {
        return str_contains($path, 'Feature') ? 'feature' : 'unit';
    });
    $progress->shouldReceive('setActiveCommand');
    $progress->shouldReceive('registerOutput')->atLeast()->once();
    $progress->shouldReceive('displayCurrentStatus')->andReturnNull();
    $progress->shouldReceive('getStatistics')->andReturn([
        'completed' => 2,
        'passed' => 1,
        'failed' => 1,
        'skipped' => 0,
        'average_duration' => 1.55,
        'summary' => [
            'status' => 'failed',
            'total_tests' => 2,
            'total_passed' => 1,
            'total_failed' => 1,
            'total_skipped' => 0,
            'total_duration' => 3.1,
            'success_rate' => 50,
        ],
    ]);

    PromptSequence::seed([
        'custom',           // main menu option
        'category',         // selection method
        ['unit', 'feature'],// categories
        true,               // include all from categories
        true,               // confirm execution
        true,               // parallel
        '2',                // processes
        false,              // stop on failure
        false,              // coverage
        '5',                // timeout minutes
    ]);

    $this->artisan('test:runner')
        ->expectsOutputToContain('🎯 Custom Test Selection')
        ->expectsOutputToContain('Test Execution Completed with Issues')
        ->assertExitCode(0);

    Event::assertDispatched(TestExecutionStarted::class);
    Event::assertDispatched(TestExecutionCompleted::class);
    Event::assertDispatched(TestCompleted::class);
});

it('honours custom output file option', function () {
    mockDiscoveryDataset();

    $execution = $this->mock(TestExecutionService::class);
    $progress = $this->mock(ProgressTrackingService::class);

    $execution->shouldReceive('startTestExecution')
        ->once()
        ->withArgs(fn ($files, $output) => $output === 'reports/custom-report.md')
        ->andReturnUsing(function () {
            TestExecutionStarted::dispatch('cmd-output-1', 0, now(), []);
            TestExecutionCompleted::dispatch('cmd-output-1', ['status' => 'passed'], now());

            return 'cmd-output-1';
        });

    $execution->shouldReceive('trackProgress')
        ->andReturn([
            'status' => 'completed',
            'completed_tests' => 0,
            'total_tests' => 0,
        ]);

    $execution->shouldReceive('getExecutionReport')->andReturn(['summary' => ['status' => 'passed'], 'output_file' => 'reports/custom-report.md']);

    $progress->shouldReceive('determineTestType')->andReturn('other');
    $progress->shouldReceive('setActiveCommand');
    $progress->shouldReceive('registerOutput')->atLeast()->once();
    $progress->shouldReceive('displayCurrentStatus')->andReturnNull();
    $progress->shouldReceive('getStatistics')->andReturn([
        'completed' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'average_duration' => 0.0,
        'summary' => ['status' => 'passed', 'total_tests' => 0, 'total_duration' => 0],
    ]);

    PromptSequence::seed([
        'all',
        true,
        true,
        '4',
        false,
        false,
        '10',
    ]);

    $this->artisan('test:runner --output-file=reports/custom-report.md')
        ->expectsOutputToContain('reports/custom-report.md')
        ->assertExitCode(0);

    Event::assertDispatched(TestExecutionStarted::class);
    Event::assertDispatched(TestExecutionCompleted::class);
});

it('reports failure when execution service throws an exception', function () {
    $this->mock(TestDiscoveryService::class, function ($mock) {
        $mock->shouldReceive('getTestStatistics')->andReturn([
            'total' => 1,
            'unit' => 1,
            'feature' => 0,
            'livewire' => 0,
            'browser' => 0,
        ]);
        $mock->shouldReceive('discoverAllTests')->andReturn([
            'unit' => [],
            'feature' => [],
            'livewire' => [],
            'browser' => [],
            'all' => [],
        ]);
    });

    $this->mock(TestExecutionService::class, function ($mock) {
        $mock->shouldReceive('startTestExecution')->andThrow(new \RuntimeException('Queue unavailable'));
    });

    $this->mock(ProgressTrackingService::class);

    PromptSequence::seed([
        'all',
        true,
        true,
        '4',
        false,
        false,
        '10',
    ]);

    $this->artisan('test:runner')
        ->expectsOutputToContain('Failed to start test execution: Queue unavailable')
        ->assertExitCode(1);

    Event::assertNothingDispatched();
});

/**
 * Helper to build prompt queue tokens.
 */
class PromptSequence
{
    /**
     * @var array<int, mixed>
     */
    private static array $values = [];

    public static function reset(): void
    {
        self::$values = [];
    }

    /**
     * @param array<int, mixed> $values
     */
    public static function seed(array $values): void
    {
        self::$values = $values;
    }

    public static function pull(mixed $default = null): mixed
    {
        return array_shift(self::$values) ?? $default;
    }
}

/**
 * Prepare discovery dataset with default structure.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
function mockDiscoveryDataset(): array
{
    $dataset = [
        'unit' => [
            [
                'file_path' => base_path('tests/Unit/FooTest.php'),
                'relative_path' => 'tests/Unit/FooTest.php',
                'category' => 'unit',
                'display_name' => 'Foo Test',
            ],
            [
                'file_path' => base_path('tests/Unit/BarTest.php'),
                'relative_path' => 'tests/Unit/BarTest.php',
                'category' => 'unit',
                'display_name' => 'Bar Test',
            ],
            [
                'file_path' => base_path('tests/Unit/BazTest.php'),
                'relative_path' => 'tests/Unit/BazTest.php',
                'category' => 'unit',
                'display_name' => 'Baz Test',
            ],
        ],
        'feature' => [
            [
                'file_path' => base_path('tests/Feature/AlphaTest.php'),
                'relative_path' => 'tests/Feature/AlphaTest.php',
                'category' => 'feature',
                'display_name' => 'Alpha Test',
            ],
            [
                'file_path' => base_path('tests/Feature/BetaTest.php'),
                'relative_path' => 'tests/Feature/BetaTest.php',
                'category' => 'feature',
                'display_name' => 'Beta Test',
            ],
        ],
        'livewire' => [],
        'browser' => [],
    ];

    $dataset['all'] = array_merge(
        $dataset['unit'],
        $dataset['feature']
    );

    $stats = [
        'total' => count($dataset['all']),
        'unit' => count($dataset['unit']),
        'feature' => count($dataset['feature']),
        'livewire' => 0,
        'browser' => 0,
    ];

    $mock = m::mock(TestDiscoveryService::class)->makePartial();
    $mock->shouldReceive('getTestStatistics')->andReturn($stats);
    $mock->shouldReceive('discoverAllTests')->andReturn($dataset);

    app()->instance(TestDiscoveryService::class, $mock);

    return $dataset;
}
