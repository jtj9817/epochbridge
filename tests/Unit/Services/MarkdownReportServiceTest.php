<?php

use App\Services\MarkdownReportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(Tests\TestCase::class);

function normalize_markdown_report(string $markdown): string
{
    $search = [
        '/_Last Updated: .*_/',
        '/\*\*PHP\*\*: .*/',
        '/\*\*Laravel\*\*: .*/',
        '/\*\*Pest\*\*: .*/',
        '/\*\*Completed At:\*\s+.*/',
    ];

    $replace = [
        '_Last Updated: 2024-10-18 12:00:00_',
        '**PHP**: x.y.z',
        '**Laravel**: vX.Y',
        '**Pest**: vA.B',
        '**Completed At:** 2024-10-18T12:05:00.000000Z',
    ];

    $normalized = preg_replace($search, $replace, $markdown);

    return trim($normalized ?? $markdown);
}

describe('MarkdownReportService', function () {
    beforeEach(function () {
        Storage::fake('reports');

        $this->service = app(MarkdownReportService::class);
        $this->startTime = CarbonImmutable::create(2024, 10, 18, 12, 0, 0, 'UTC');
        CarbonImmutable::setTestNow($this->startTime);

        $this->relativeOutput = 'storage/framework/testing/'.Str::uuid()->toString().'.md';
        $this->absoluteOutput = base_path($this->relativeOutput);

        File::ensureDirectoryExists(dirname($this->absoluteOutput));
    });

    afterEach(function () {
        CarbonImmutable::setTestNow();

        if (File::exists($this->absoluteOutput)) {
            File::delete($this->absoluteOutput);
        }
    });

    it('initializes a markdown report with populated metadata', function () {
        $this->service->initializeReport($this->relativeOutput, 42, $this->startTime);

        $contents = File::get($this->absoluteOutput);

        expect($contents)
            ->toContain('# EpochBridge Test Execution Report')
            ->toContain('_Last Updated: '.$this->startTime->toDateTimeString().'_')
            ->toContain('**PHP**: '.PHP_VERSION)
            ->toContain('| Total Tests | 42 |');
    });

    it('streams test results into the correct sections and records failures', function () {
        $this->service->initializeReport($this->relativeOutput, 3, $this->startTime);

        $this->service->updateTestResult(
            $this->relativeOutput,
            base_path('tests/Unit/ExampleTest.php'),
            'passed',
            0.382,
            'OK',
            ''
        );

        $this->service->updateTestResult(
            $this->relativeOutput,
            base_path('tests/Feature/FailingFeatureTest.php'),
            'failed',
            1.452,
            'Output chunk',
            "tests/Feature/FailingFeatureTest.php:42\nStack trace…"
        );

        $contents = File::get($this->absoluteOutput);

        expect($contents)
            ->toContain('| [`ExampleTest`](tests/Unit/ExampleTest.php) | ✅ Passed | ⏱️ 0.38s | Output captured |')
            ->toContain('| [`FailingFeatureTest`](tests/Feature/FailingFeatureTest.php) | ❌ Failed | ⏱️ 1.5s | See failure details below |')
            ->toContain('### ❌ `FailingFeatureTest`')
            ->toContain('`tests/Feature/FailingFeatureTest.php:42`')
            ->toContain('```bash'.PHP_EOL.'Output chunk');
    });

    it('generates executive summary and performance sections', function () {
        $this->service->initializeReport($this->relativeOutput, 4, $this->startTime);

        $this->service->generateSummarySection($this->relativeOutput, [
            'total_tests' => 4,
            'total_passed' => 3,
            'total_failed' => 1,
            'total_skipped' => 0,
            'total_duration' => 12.8,
            'total_assertions' => 20,
            'success_rate' => 75,
            'average_duration' => 3.2,
            'parallel_efficiency' => 88.5,
            'command' => 'php artisan test --parallel',
            'executed_at' => $this->startTime->addMinutes(5)->toISOString(),
            'processes' => 4,
            'slowest_tests' => [
                ['file' => 'tests/Feature/SlowTest.php', 'duration' => 6.45],
                ['file' => 'tests/Unit/AnotherTest.php', 'duration' => 3.21],
            ],
        ]);

        $contents = File::get($this->absoluteOutput);
        $normalized = normalize_markdown_report($contents);

        expect($normalized)
            ->toContain('| Metric | Value |')
            ->toContain('| Total Tests | 4 |')
            ->toContain('| Failed | 1 |')
            ->toContain('| Duration | ⏱️ 12.8s |')
            ->toContain('<!-- PERFORMANCE_METRICS_START -->')
            ->toContain('#### Slowest Tests')
            ->toContain('| `tests/Feature/SlowTest.php` | ⏱️ 6.5s |')
            ->toContain('| `tests/Unit/AnotherTest.php` | ⏱️ 3.2s |')
            ->toContain('**Parallel Efficiency:** 88.5%')
            ->toContain('**Command:** `php artisan test --parallel`')
            ->toContain('**Completed At:** 2024-10-18T12:05:00.000000Z');
    });

    it('formats test output as fenced code blocks', function () {
        $result = $this->service->formatTestOutput("Line A\nLine B");

        expect($result)->toBe("```bash\nLine A\nLine B\n```".PHP_EOL);
    });

    it('formats error details as collapsible sections', function () {
        $errors = "/path/to/TestFile.php:15\nMore info";

        $result = $this->service->formatErrorDetails($errors);

        expect($result)
            ->toContain('<details>')
            ->toContain('<summary><strong>Error Details</strong></summary>')
            ->toContain('```text')
            ->toContain('`/path/to/TestFile.php:15`');
    });

    it('supports concurrent writes without corrupting result tables', function () {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension is not available.');
        }

        $this->service->initializeReport($this->relativeOutput, 3, $this->startTime);

        $writers = 3;
        $pids = [];

        for ($i = 1; $i <= $writers; $i++) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                /** @var MarkdownReportService $svc */
                $svc = app(MarkdownReportService::class);
                $svc->updateTestResult(
                    $this->relativeOutput,
                    base_path("tests/Unit/Concurrent{$i}Test.php"),
                    'passed',
                    0.15 * $i,
                    '',
                    ''
                );
                exit(0);
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $contents = File::get($this->absoluteOutput);
        $occurrences = substr_count($contents, '| [`Concurrent');

        expect($occurrences)->toBe($writers);
    });

    it('handles large outputs and unicode safely', function () {
        $this->service->initializeReport($this->relativeOutput, 1, $this->startTime);

        $unicodeOutput = "Διαγνωστικός έλεγχος\n✔ passed";
        $errors = "C:\\Tests\\ExampleTest.php:88\nUnexpected 💥";

        $this->service->updateTestResult(
            $this->relativeOutput,
            base_path('tests/Feature/UnicodeTest.php'),
            'failed',
            0.25,
            $unicodeOutput,
            $errors
        );

        $contents = File::get($this->absoluteOutput);

        expect($contents)
            ->toContain('Διαγνωστικός έλεγχος')
            ->toContain('ExampleTest.php:88')
            ->toContain('💥');
    });
});
