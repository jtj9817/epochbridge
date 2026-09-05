<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarkdownReportService
{
    /**
     * Initialize the markdown report with the base template and metadata.
     */
    public function initializeReport(string $outputFile, int $totalTests, CarbonInterface $startTime): void
    {
        $path = $this->resolveOutputPath($outputFile);

        try {
            File::ensureDirectoryExists(dirname($path));

            $templatePath = resource_path('views/test-report-template.md');
            if (! File::exists($templatePath)) {
                Log::warning('Markdown template missing', ['template_path' => $templatePath]);
                $initialContent = $this->generateFallbackTemplate($totalTests, $startTime);
            } else {
                $template = File::get($templatePath);
                $initialContent = $this->populateTemplate($template, [
                    '{{title}}' => 'EpochBridge Test Execution Report',
                    '{{timestamp}}' => $startTime->toDateTimeString(),
                    '{{php_version}}' => PHP_VERSION,
                    '{{laravel_version}}' => app()->version(),
                    '{{pest_version}}' => $this->getPestVersion(),
                    '{{total_tests}}' => (string) $totalTests,
                ]);
            }

            $this->writeAtomically($path, $initialContent);
        } catch (\Throwable $exception) {
            Log::error('Failed to initialize markdown report', [
                'output_file' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Append an individual test result into the markdown report in real time.
     */
    public function updateTestResult(
        string $outputFile,
        string $testFile,
        string $status,
        float $duration,
        string $output = '',
        string $errors = ''
    ): void {
        $path = $this->resolveOutputPath($outputFile);

        $this->withLockedFile($path, function ($handle) use ($testFile, $status, $duration, $output, $errors) {
            $content = stream_get_contents($handle) ?: '';

            $badge = $this->getStatusBadge($status);
            $durationText = $this->formatDuration($duration);
            $relativePath = $this->relativeTestPath($testFile);
            $testName = $this->displayTestName($testFile);
            $notes = $this->buildNotesColumn($status, $output, $errors);

            $row = sprintf(
                '| [`%s`](%s) | %s | ⏱️ %s | %s |'.PHP_EOL,
                $testName,
                $relativePath,
                $badge,
                $durationText,
                $notes
            );

            $marker = $this->determineResultMarker($relativePath);
            $content = $this->injectBeforeMarker($content, $marker, $row);

            if ($this->isFailureStatus($status)) {
                $failureSection = $this->buildFailureSection($testName, $relativePath, $durationText, $output, $errors);
                $content = $this->injectBeforeMarker($content, '<!-- FAILED_TESTS -->', $failureSection.PHP_EOL);
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $content);
            fflush($handle);
        });
    }

    /**
     * Generate the summary section and finalize the report.
     */
    public function generateSummarySection(string $outputFile, array $summary): void
    {
        $path = $this->resolveOutputPath($outputFile);

        $this->withLockedFile($path, function ($handle) use ($summary) {
            $content = stream_get_contents($handle) ?: '';

            $executiveSummary = $this->buildExecutiveSummaryTable($summary);
            $performanceMetrics = $this->buildPerformanceMetrics($summary);
            $footer = $this->buildFooter($summary);
            $lastUpdated = now()->toDateTimeString();

            $content = $this->replaceBetweenMarkers(
                $content,
                '<!-- EXECUTIVE_SUMMARY_START -->',
                '<!-- EXECUTIVE_SUMMARY_END -->',
                $executiveSummary
            );

            $content = $this->replaceBetweenMarkers(
                $content,
                '<!-- PERFORMANCE_METRICS_START -->',
                '<!-- PERFORMANCE_METRICS_END -->',
                $performanceMetrics
            );

            $content = $this->replaceBetweenMarkers(
                $content,
                '<!-- FOOTER_START -->',
                '<!-- FOOTER_END -->',
                $footer
            );

            $content = preg_replace(
                '/(<!-- LAST_UPDATED -->)(.*?)(<!-- END_LAST_UPDATED -->)/s',
                '$1'.PHP_EOL.'_Last Updated: '.$lastUpdated.'_'
                    .PHP_EOL.'$3',
                $content
            );

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $content);
            fflush($handle);
        });
    }

    /**
     * Format raw test output into a fenced code block with escaping.
     */
    public function formatTestOutput(string $output): string
    {
        $output = trim($output);

        if ($output === '') {
            return '';
        }

        return '```bash'.PHP_EOL
            .rtrim($output).PHP_EOL
            .'```'.PHP_EOL;
    }

    /**
     * Format error stack traces and messages for markdown output.
     */
    public function formatErrorDetails(string $errors): string
    {
        $errors = trim($errors);
        if ($errors === '') {
            return '';
        }

        $highlighted = preg_replace_callback(
            '/([\/\w\.\-]+\.php:\d+)/',
            static fn ($matches) => sprintf('`%s`', $matches[1]),
            $errors
        ) ?? $errors;

        return '<details>'.PHP_EOL
            .'<summary><strong>Error Details</strong></summary>'.PHP_EOL.PHP_EOL
            .'```text'.PHP_EOL
            .rtrim($highlighted).PHP_EOL
            .'```'.PHP_EOL
            .'</details>'.PHP_EOL;
    }

    /**
     * Resolve the absolute path for the output file.
     */
    private function resolveOutputPath(string $outputFile): string
    {
        if (Str::startsWith($outputFile, ['/'])) {
            return $outputFile;
        }

        return base_path($outputFile);
    }

    /**
     * Write content to a file atomically with an exclusive lock.
     */
    private function writeAtomically(string $path, string $content): void
    {
        $this->withLockedFile($path, function ($handle) use ($content) {
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $content);
            fflush($handle);
        }, 'c+');
    }

    /**
     * Execute a callback with an exclusive lock on a file handle.
     */
    private function withLockedFile(string $path, callable $callback, string $mode = 'c+'): void
    {
        $handle = fopen($path, $mode);

        if ($handle === false) {
            Log::error('Unable to open markdown report file', ['path' => $path]);

            return;
        }

        $locked = false;

        try {
            if (! flock($handle, LOCK_EX)) {
                Log::warning('Could not acquire file lock for markdown report', ['path' => $path]);
                fclose($handle);

                return;
            }
            $locked = true;

            $callback($handle);
        } catch (\Throwable $exception) {
            Log::error('Markdown report operation failed', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }
            fclose($handle);
        }
    }

    /**
     * Populate template placeholders with their values.
     *
     * @param  array<string, string>  $data
     */
    private function populateTemplate(string $template, array $data): string
    {
        return strtr($template, $data);
    }

    /**
     * Generate a fallback template when the primary template is unavailable.
     */
    private function generateFallbackTemplate(int $totalTests, CarbonInterface $startTime): string
    {
        $content = '# EpochBridge Test Execution Report'.PHP_EOL.PHP_EOL;
        $content .= '_Last Updated: '.$startTime->toDateTimeString().'_'.PHP_EOL.PHP_EOL;
        $content .= '## Executive Summary'.PHP_EOL;
        $content .= '<!-- EXECUTIVE_SUMMARY_START -->'.PHP_EOL;
        $content .= '| Metric | Value |'.PHP_EOL;
        $content .= '| --- | --- |'.PHP_EOL;
        $content .= '| Total Tests | '.$totalTests.' |'.PHP_EOL;
        $content .= '| Passed | 0 |'.PHP_EOL;
        $content .= '| Failed | 0 |'.PHP_EOL;
        $content .= '| Skipped | 0 |'.PHP_EOL;
        $content .= '| Pass Rate | 0% |'.PHP_EOL;
        $content .= '| Duration | 0s |'.PHP_EOL;
        $content .= '<!-- EXECUTIVE_SUMMARY_END -->'.PHP_EOL.PHP_EOL;
        $content .= '## Test Results by Type'.PHP_EOL;
        $content .= $this->fallbackResultsSection();
        $content .= '## Failed Tests'.PHP_EOL.'<!-- FAILED_TESTS -->'.PHP_EOL.PHP_EOL;
        $content .= '## Performance Metrics'.PHP_EOL;
        $content .= '<!-- PERFORMANCE_METRICS_START -->'.PHP_EOL;
        $content .= '_Pending_'.PHP_EOL;
        $content .= '<!-- PERFORMANCE_METRICS_END -->'.PHP_EOL.PHP_EOL;
        $content .= '<!-- FOOTER_START -->'.PHP_EOL;
        $content .= '_Awaiting completion_'.PHP_EOL;
        $content .= '<!-- FOOTER_END -->'.PHP_EOL;

        return $content;
    }

    /**
     * Build the fallback results section markup.
     */
    private function fallbackResultsSection(): string
    {
        $sections = [
            'Unit Tests' => '<!-- UNIT_RESULTS -->',
            'Feature Tests' => '<!-- FEATURE_RESULTS -->',
            'Browser Tests' => '<!-- BROWSER_RESULTS -->',
            'Other Tests' => '<!-- OTHER_RESULTS -->',
        ];

        $buffer = '';

        foreach ($sections as $header => $marker) {
            $buffer .= '### '.$header.PHP_EOL;
            $buffer .= '| Test | Status | Duration | Details |'.PHP_EOL;
            $buffer .= '| --- | --- | --- | --- |'.PHP_EOL;
            $buffer .= $marker.PHP_EOL.PHP_EOL;
        }

        return $buffer;
    }

    /**
     * Build the notes column content based on status and output.
     */
    private function buildNotesColumn(string $status, string $output, string $errors): string
    {
        if ($this->isFailureStatus($status) && $errors !== '') {
            return 'See failure details below';
        }

        if ($output !== '') {
            return 'Output captured';
        }

        if ($status === 'skipped') {
            return 'Marked as skipped';
        }

        return 'N/A';
    }

    /**
     * Build the failure section block for failed tests.
     */
    private function buildFailureSection(
        string $testName,
        string $relativePath,
        string $duration,
        string $output,
        string $errors
    ): string {
        $section = '### ❌ `'.$testName.'`'.PHP_EOL;
        $section .= '- File: `'.$relativePath.'`'.PHP_EOL;
        $section .= '- Duration: ⏱️ '.$duration.PHP_EOL.PHP_EOL;

        $formattedErrors = $this->formatErrorDetails($errors);
        if ($formattedErrors !== '') {
            $section .= $formattedErrors.PHP_EOL;
        }

        $formattedOutput = $this->formatTestOutput($output);
        if ($formattedOutput !== '') {
            $section .= '<details>'.PHP_EOL;
            $section .= '<summary><strong>Test Output</strong></summary>'.PHP_EOL.PHP_EOL;
            $section .= $formattedOutput;
            $section .= '</details>'.PHP_EOL;
        }

        return $section;
    }

    /**
     * Build executive summary table Markdown.
     */
    private function buildExecutiveSummaryTable(array $summary): string
    {
        $rows = [
            ['Total Tests', Arr::get($summary, 'total_tests', 0)],
            ['Passed', Arr::get($summary, 'total_passed', 0)],
            ['Failed', Arr::get($summary, 'total_failed', 0)],
            ['Skipped', Arr::get($summary, 'total_skipped', 0)],
            ['Pass Rate', Arr::get($summary, 'success_rate', 0).'%'],
            ['Duration', '⏱️ '.$this->formatDuration(Arr::get($summary, 'total_duration', 0.0))],
            ['Assertions', Arr::get($summary, 'total_assertions', 0)],
        ];

        $buffer = '| Metric | Value |'.PHP_EOL;
        $buffer .= '| --- | --- |'.PHP_EOL;

        foreach ($rows as $row) {
            $buffer .= sprintf('| %s | %s |', $row[0], $row[1]).PHP_EOL;
        }

        return $buffer;
    }

    /**
     * Build performance metrics section content.
     */
    private function buildPerformanceMetrics(array $summary): string
    {
        $metrics = [];

        $slowest = Arr::get($summary, 'slowest_tests');
        if (! empty($slowest)) {
            $metrics[] = $this->buildSlowestTestsTable($slowest);
        }

        $avg = Arr::get($summary, 'average_duration');
        if ($avg !== null) {
            $metrics[] = '- **Average Test Duration:** ⏱️ '.$this->formatDuration((float) $avg);
        }

        $efficiency = Arr::get($summary, 'parallel_efficiency');
        if ($efficiency !== null) {
            $metrics[] = '- **Parallel Efficiency:** '.round((float) $efficiency, 2).'%';
        }

        if ($metrics === []) {
            return '_No performance metrics available_';
        }

        return implode(PHP_EOL.PHP_EOL, $metrics);
    }

    /**
     * Build the footer section.
     */
    private function buildFooter(array $summary): string
    {
        $command = Arr::get($summary, 'command', 'php artisan test --parallel');
        $completedAt = Arr::get($summary, 'executed_at', now()->toISOString());
        $processes = Arr::get($summary, 'processes');

        $footer = '**Command:** `'.$command.'`'.PHP_EOL;

        if ($processes) {
            $footer .= '**Parallel Processes:** '.$processes.PHP_EOL;
        }

        $footer .= '**Completed At:** '.$completedAt;

        return $footer;
    }

    /**
     * Build the slowest tests markdown table.
     */
    private function buildSlowestTestsTable(iterable $slowest): string
    {
        $buffer = '#### Slowest Tests'.PHP_EOL;
        $buffer .= '| Test | Duration |'.PHP_EOL;
        $buffer .= '| --- | --- |'.PHP_EOL;

        foreach ($slowest as $item) {
            $name = Arr::get($item, 'file') ?? Arr::get($item, 'name', 'Unknown');
            $duration = $this->formatDuration((float) Arr::get($item, 'duration', 0));
            $buffer .= sprintf('| `%s` | ⏱️ %s |', $name, $duration).PHP_EOL;
        }

        return $buffer;
    }

    /**
     * Determine the appropriate marker for the test results section.
     */
    private function determineResultMarker(string $relativePath): string
    {
        $relativePath = strtolower($relativePath);

        if (str_contains($relativePath, 'tests/unit')) {
            return '<!-- UNIT_RESULTS -->';
        }

        if (str_contains($relativePath, 'tests/feature')) {
            return '<!-- FEATURE_RESULTS -->';
        }

        if (str_contains($relativePath, 'browser')) {
            return '<!-- BROWSER_RESULTS -->';
        }

        return '<!-- OTHER_RESULTS -->';
    }

    /**
     * Insert content directly before a marker comment.
     */
    private function injectBeforeMarker(string $content, string $marker, string $insertion): string
    {
        if (! str_contains($content, $marker)) {
            return $content.PHP_EOL.$insertion;
        }

        return str_replace(
            $marker,
            $insertion.$marker,
            $content
        );
    }

    /**
     * Replace the content found between two markers.
     */
    private function replaceBetweenMarkers(string $content, string $start, string $end, string $replacement): string
    {
        $pattern = sprintf('/%s(.*)%s/sU', preg_quote($start, '/'), preg_quote($end, '/'));

        if (preg_match($pattern, $content) === 1) {
            return preg_replace(
                $pattern,
                $start.PHP_EOL.$replacement.PHP_EOL.$end,
                $content
            ) ?? $content;
        }

        return $content;
    }

    /**
     * Determine if a status represents a failed execution.
     */
    private function isFailureStatus(string $status): bool
    {
        return in_array($status, ['failed', 'error'], true);
    }

    /**
     * Retrieve the status badge for a given status.
     */
    private function getStatusBadge(string $status): string
    {
        return match ($status) {
            'passed' => '✅ Passed',
            'skipped' => '⏭️ Skipped',
            'failed', 'error' => '❌ Failed',
            default => Str::title($status),
        };
    }

    /**
     * Format a duration into a human readable string.
     */
    private function formatDuration(float $duration): string
    {
        if ($duration < 0.01) {
            return '< 0.01s';
        }

        if ($duration < 1) {
            return number_format($duration, 2).'s';
        }

        if ($duration < 60) {
            return number_format($duration, 1).'s';
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return $minutes.'m '.number_format($seconds, 1).'s';
    }

    /**
     * Build a human readable test name.
     */
    private function displayTestName(string $testFile): string
    {
        return pathinfo($testFile, PATHINFO_FILENAME);
    }

    /**
     * Retrieve the path relative to the base test directory.
     */
    private function relativeTestPath(string $testFile): string
    {
        $relative = Str::after($testFile, base_path().DIRECTORY_SEPARATOR);

        return $relative !== $testFile ? $relative : $testFile;
    }

    /**
     * Retrieve the installed PestPHP version if available.
     */
    private function getPestVersion(): string
    {
        if (class_exists(\Pest\Version::class)) {
            try {
                return \Pest\Version::get();
            } catch (\Throwable $exception) {
                Log::debug('Unable to determine Pest version', ['error' => $exception->getMessage()]);
            }
        }

        return 'unknown';
    }
}
