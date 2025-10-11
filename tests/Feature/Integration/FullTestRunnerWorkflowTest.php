<?php

use App\Contracts\TestProcessFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery as m;
use Symfony\Component\Process\Process;


beforeEach(function () {
    Config::set('queue.default', 'sync');

    $this->originalRunnerConfig = config('test-runner');

    $this->integrationRoot = base_path('tests/tmp/integration');

    if (File::exists($this->integrationRoot)) {
        File::deleteDirectory($this->integrationRoot);
    }

    File::ensureDirectoryExists($this->integrationRoot.'/unit');
    File::ensureDirectoryExists($this->integrationRoot.'/feature');

    File::put($this->integrationRoot.'/unit/InlineUnitTest.php', <<<PHP
<?php

test('inline unit passes', function () {
    expect(true)->toBeTrue();
});
PHP);

    File::put($this->integrationRoot.'/feature/InlineFeatureTest.php', <<<PHP
<?php

test('inline feature fails', function () {
    expect(false)->toBeTrue();
});
PHP);

    Config::set('test-runner.paths.unit', 'tests/tmp/integration/unit');
    Config::set('test-runner.paths.feature', 'tests/tmp/integration/feature');
    Config::set('test-runner.paths.livewire', '');
    Config::set('test-runner.paths.browser', '');
    Config::set('test-runner.patterns.unit', 'tests/tmp/integration/unit/*Test.php');
    Config::set('test-runner.patterns.feature', 'tests/tmp/integration/feature/*Test.php');
});

afterEach(function () {
    if (isset($this->originalRunnerConfig)) {
        Config::set('test-runner', $this->originalRunnerConfig);
    }

    app()->forgetInstance(TestProcessFactory::class);

    if (isset($this->integrationRoot) && File::exists($this->integrationRoot)) {
        File::deleteDirectory($this->integrationRoot);
    }

    m::close();
});

it('executes end-to-end workflow and generates markdown report', function () {
    $reportRelative = 'storage/framework/testing/'.Str::uuid()->toString().'.md';
    $reportAbsolute = base_path($reportRelative);

    $process = m::mock(Process::class);

    $attempt = 0;
    $responses = [
        [
            'exit' => 0,
            'output' => "Tests: 1 passed\nAssertions: 2",
            'error' => '',
        ],
        [
            'exit' => 1,
            'output' => "Tests: 0 passed, 1 failed\nAssertions: 1",
            'error' => "Assertion failed at InlineFeatureTest.php:5",
        ],
    ];

    $process->shouldReceive('setTimeout')->twice()->andReturnNull();
    $process->shouldReceive('run')->twice()->andReturnUsing(function () use (&$attempt) {
        $attempt++;
        return null;
    });
    $process->shouldReceive('getOutput')->andReturnUsing(function () use (&$attempt, $responses) {
        return $responses[$attempt - 1]['output'];
    });
    $process->shouldReceive('getErrorOutput')->andReturnUsing(function () use (&$attempt, $responses) {
        return $responses[$attempt - 1]['error'];
    });
    $process->shouldReceive('getExitCode')->andReturnUsing(function () use (&$attempt, $responses) {
        return $responses[$attempt - 1]['exit'];
    });

    $factory = m::mock(TestProcessFactory::class);
    $factory->shouldReceive('make')
        ->twice()
        ->withArgs(function (string $command, ?string $cwd) {
            return str_starts_with($command, 'php artisan test') && $cwd === base_path();
        })
        ->andReturn($process);

    app()->instance(TestProcessFactory::class, $factory);

    PromptSequence::seed([
        'custom',               // main menu
        'category',             // selection method
        ['unit', 'feature'],    // categories
        true,                   // include all tests
        true,                   // confirm execution
        true,                   // parallel
        '2',                    // processes
        false,                  // stop on first failure
        false,                  // coverage
        '5',                    // timeout minutes
    ]);

    $this->artisan("test:runner --output-file={$reportRelative}")
        ->expectsOutputToContain('Real-time Test Execution Progress')
        ->expectsOutputToContain('Test Execution Completed with Issues')
        ->assertExitCode(0);

    expect(File::exists($reportAbsolute))->toBeTrue();

    $markdown = File::get($reportAbsolute);

    expect($markdown)
        ->toContain('InlineUnitTest')
        ->toContain('InlineFeatureTest')
        ->toContain('❌ Failed');
});
