<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProgressTrackingService
{
    /**
     * Cached execution progress keyed by command ID.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $executions = [];

    /**
     * Currently active execution identifier.
     */
    private ?string $currentCommandId = null;

    /**
     * Console output instance used for rendering.
     */
    private ?OutputInterface $output = null;

    /**
     * Register the console output for rendering progress.
     */
    public function registerOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Initialise a new progress record for a command.
     *
     * @param  array<string, mixed>  $testTypes
     * @param  array<string, mixed>  $context
     */
    public function initializeProgress(
        string $commandId,
        int $totalTests,
        array $testTypes,
        CarbonInterface $startTime,
        array $context = []
    ): void {
        $totalTests = max($totalTests, Arr::get($context, 'estimated_total_tests', 0));

        $this->currentCommandId = $commandId;

        $typeTemplate = [
            'total' => 0,
            'completed' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $byType = [
            'unit' => $typeTemplate,
            'feature' => $typeTemplate,
            'browser' => $typeTemplate,
            'other' => $typeTemplate,
        ];

        foreach ($testTypes as $type => $count) {
            if (! isset($byType[$type])) {
                $byType[$type] = $typeTemplate;
            }
            $byType[$type]['total'] = (int) $count;
        }

        $this->executions[$commandId] = [
            'total' => $totalTests,
            'completed' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'average_duration' => 0.0,
            'duration_sum' => 0.0,
            'start_time' => $startTime,
            'last_render' => 0.0,
            'lines_rendered' => 0,
            'by_type' => $byType,
            'recent' => [],
            'pending' => array_values(Arr::get($context, 'test_files', [])),
            'currently_running' => [],
            'concurrency' => (int) Arr::get($context, 'options.processes', 1),
            'supports_ansi' => $this->detectAnsiSupport(),
            'summary' => null,
        ];
    }

    /**
     * Update progress for a completed test.
     */
    public function updateProgress(string $commandId, string $testFile, string $status, float $duration): void
    {
        if (! isset($this->executions[$commandId])) {
            Log::debug('Attempted to update progress for unknown command', [
                'command_id' => $commandId,
            ]);

            return;
        }

        $execution = &$this->executions[$commandId];
        $execution['completed']++;
        $execution['duration_sum'] += max(0.0, $duration);
        $execution['average_duration'] = $execution['completed'] > 0
            ? $execution['duration_sum'] / $execution['completed']
            : 0.0;

        $type = $this->determineTestType($testFile);

        if (! isset($execution['by_type'][$type])) {
            $execution['by_type'][$type] = [
                'total' => 0,
                'completed' => 0,
                'passed' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $execution['by_type'][$type]['completed']++;

        match ($status) {
            'passed' => [
                $execution['passed']++,
                $execution['by_type'][$type]['passed']++,
            ],
            'failed', 'error' => [
                $execution['failed']++,
                $execution['by_type'][$type]['failed']++,
            ],
            'skipped' => [
                $execution['skipped']++,
                $execution['by_type'][$type]['skipped']++,
            ],
            default => null,
        };

        $execution['recent'][] = [
            'file' => $testFile,
            'status' => $status,
            'duration' => $duration,
            'type' => $type,
        ];

        $execution['recent'] = array_slice($execution['recent'], -10);

        if (! empty($execution['pending'])) {
            $execution['pending'] = array_values(array_filter(
                $execution['pending'],
                static fn ($file) => $file !== $testFile
            ));
        }

        $this->recomputeRunningList($execution);
    }

    /**
     * Display the current status to the console.
     */
    public function displayCurrentStatus(
        ?OutputInterface $output = null,
        ?string $commandId = null,
        bool $force = false
    ): void {
        $output = $output ?? $this->output;
        $commandId = $commandId ?? $this->currentCommandId;

        if ($output === null || $commandId === null) {
            return;
        }

        if (! isset($this->executions[$commandId])) {
            return;
        }

        $execution = &$this->executions[$commandId];

        $now = microtime(true);
        if (! $force && ($now - $execution['last_render']) < 0.1) {
            return;
        }
        $execution['last_render'] = $now;

        $supportsAnsi = $execution['supports_ansi'] && $output->isDecorated();
        $width = $this->resolveTerminalWidth($output);
        $barWidth = max(10, $width - 40);

        $percentage = $this->getCompletionPercentage($execution['completed'], $execution['total']);
        $overallBar = $this->getProgressBar($execution['completed'], max($execution['total'], 1), $barWidth);
        $eta = $this->calculateETA(
            $execution['completed'],
            max($execution['total'], 1),
            $execution['start_time'],
            $execution['average_duration']
        );
        $elapsed = $execution['start_time']->diffForHumans(null, true);

        $lines = [];
        $lines[] = sprintf(
            ' Overall %s %d/%d tests (%0.1f%%)',
            $overallBar,
            $execution['completed'],
            $execution['total'] > 0 ? $execution['total'] : $execution['completed'],
            $percentage
        );

        foreach (['unit' => 'Unit', 'feature' => 'Feature', 'browser' => 'Browser'] as $typeKey => $typeLabel) {
            $typeStats = $execution['by_type'][$typeKey] ?? ['completed' => 0, 'total' => 0];
            $bar = $this->getProgressBar($typeStats['completed'], max(1, $typeStats['total']), (int) floor($barWidth * 0.6));
            $lines[] = sprintf(
                '  %s %s %d/%d',
                str_pad($typeLabel, 8),
                $bar,
                $typeStats['completed'],
                $typeStats['total'] ?: $typeStats['completed']
            );
        }

        $lines[] = sprintf(
            ' ✅ %d   ❌ %d   ⏭️  %d   ⏱️  %s elapsed   ETA %s',
            $execution['passed'],
            $execution['failed'],
            $execution['skipped'],
            $elapsed,
            $eta
        );

        if (! empty($execution['currently_running'])) {
            $running = implode(', ', array_slice($execution['currently_running'], 0, 4));
            $lines[] = ' Running: '.$running.(count($execution['currently_running']) > 4 ? ', …' : '');
        } elseif (! empty($execution['pending'])) {
            $queued = implode(', ', array_slice($execution['pending'], 0, 4));
            $lines[] = ' Pending: '.$queued.(count($execution['pending']) > 4 ? ', …' : '');
        } else {
            $lines[] = ' Running: Idle';
        }

        if (! empty($execution['recent'])) {
            $last = end($execution['recent']);
            $lines[] = sprintf(
                ' Last: %s (%s, %s)',
                $last['file'],
                strtoupper($last['status']),
                $this->formatDuration($last['duration'])
            );
        }

        $lineCount = count($lines);

        if ($supportsAnsi && $execution['lines_rendered'] > 0) {
            $this->clearPreviousLines($output, $execution['lines_rendered']);
        }

        foreach ($lines as $line) {
            $output->writeln($supportsAnsi ? "\033[2K{$line}" : $line);
        }

        $execution['lines_rendered'] = $lineCount;
    }

    /**
     * Calculate the estimated time remaining based on average duration.
     */
    public function calculateETA(
        int $completed,
        int $total,
        CarbonInterface $startTime,
        float $averageDuration = 0.0
    ): string {
        $averageDuration = $averageDuration > 0
            ? $averageDuration
            : $this->getAverageDurationSeconds();

        if ($averageDuration <= 0 || $total === 0 || $completed >= $total) {
            return '—';
        }

        $remaining = max($total - $completed, 0);
        $remainingSeconds = $remaining * $averageDuration;

        return $this->formatDuration($remainingSeconds);
    }

    /**
     * Generate an ASCII progress bar for display.
     */
    public function getProgressBar(int $completed, int $total, int $width = 30): string
    {
        $total = max($total, 1);
        $ratio = min(max($completed / $total, 0.0), 1.0);
        $filledLength = (int) floor($ratio * $width);
        $filled = str_repeat('█', $filledLength);
        $empty = str_repeat('░', max($width - $filledLength, 0));
        $percentage = number_format($ratio * 100, 1);

        return sprintf('[%s%s] %s%%', $filled, $empty, $percentage);
    }

    /**
     * Get the current statistics for the active command.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(?string $commandId = null): array
    {
        $commandId = $commandId ?? $this->currentCommandId;

        if ($commandId === null || ! isset($this->executions[$commandId])) {
            return [];
        }

        $execution = $this->executions[$commandId];

        return [
            'total' => $execution['total'],
            'completed' => $execution['completed'],
            'passed' => $execution['passed'],
            'failed' => $execution['failed'],
            'skipped' => $execution['skipped'],
            'average_duration' => $execution['average_duration'],
            'elapsed' => $execution['start_time']->diffInSeconds(now()),
            'by_type' => $execution['by_type'],
            'summary' => $execution['summary'],
        ];
    }

    /**
     * Persist the final summary for the execution.
     *
     * @param  array<string, mixed>  $summary
     */
    public function finalizeSummary(string $commandId, array $summary): void
    {
        if (isset($this->executions[$commandId])) {
            $this->executions[$commandId]['summary'] = $summary;
        }
    }

    /**
     * Set the command ID to operate on.
     */
    public function setActiveCommand(string $commandId): void
    {
        $this->currentCommandId = $commandId;
    }

    /**
     * Remove any existing rendering artefacts.
     */
    public function clear(): void
    {
        $this->executions = [];
        $this->currentCommandId = null;
    }

    /**
     * Determine the test type based on file path patterns.
     */
    public function determineTestType(string $testFile): string
    {
        $path = strtolower($testFile);

        return match (true) {
            str_contains($path, 'tests/unit') => 'unit',
            str_contains($path, 'tests/feature') => 'feature',
            str_contains($path, 'browser') || str_contains($path, 'dusk') => 'browser',
            default => 'other',
        };
    }

    /**
     * Format the duration into a friendly string.
     */
    private function formatDuration(float|int $seconds): string
    {
        $seconds = (float) $seconds;

        if ($seconds <= 0) {
            return '0.00s';
        }

        if ($seconds < 1) {
            return number_format($seconds, 2).'s';
        }

        if ($seconds < 60) {
            return number_format($seconds, 1).'s';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%dm %0.1fs', $minutes, $remainingSeconds);
    }

    /**
     * Clear previously rendered lines in the console.
     */
    private function clearPreviousLines(OutputInterface $output, int $lineCount): void
    {
        for ($i = 0; $i < $lineCount; $i++) {
            $output->write("\033[2K\r");
            if ($i < $lineCount - 1) {
                $output->write("\033[1A");
            }
        }
        if ($lineCount > 0) {
            $output->write("\033[2K\r");
        }
    }

    /**
     * Determine whether the environment supports ANSI escape sequences.
     */
    private function detectAnsiSupport(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') ? @stream_isatty(STDOUT) : true;
    }

    /**
     * Resolve the console width if possible.
     */
    private function resolveTerminalWidth(OutputInterface $output): int
    {
        if ($output instanceof ConsoleOutputInterface) {
            return $output->getTerminal()->getWidth();
        }

        return 100;
    }

    /**
     * Calculate completion percentage.
     */
    private function getCompletionPercentage(int $completed, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return min(100.0, max(0.0, ($completed / $total) * 100));
    }

    /**
     * Retrieve average duration for the current execution.
     */
    private function getAverageDurationSeconds(): float
    {
        if ($this->currentCommandId === null) {
            return 0.0;
        }

        return $this->executions[$this->currentCommandId]['average_duration'] ?? 0.0;
    }

    /**
     * Recompute the currently running tests list based on concurrency settings.
     *
     * @param  array<string, mixed>  $execution
     */
    private function recomputeRunningList(array &$execution): void
    {
        $concurrency = max(1, (int) $execution['concurrency']);

        if (! empty($execution['pending'])) {
            $execution['currently_running'] = array_slice($execution['pending'], 0, $concurrency);
        } else {
            $execution['currently_running'] = [];
        }
    }
}
