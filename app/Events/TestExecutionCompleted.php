<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class TestExecutionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique command identifier
     */
    public string $commandId;

    /**
     * Execution summary data
     */
    public array $summary;

    /**
     * Execution end time
     */
    public Carbon $endTime;

    /**
     * Additional execution metadata
     */
    public array $metadata;

    /**
     * Create a new event instance
     *
     * @param string $commandId Unique command identifier
     * @param array $summary Execution summary data
     * @param Carbon $endTime When execution ended
     * @param array $metadata Additional execution metadata
     */
    public function __construct(string $commandId, array $summary, Carbon $endTime, array $metadata = [])
    {
        $this->commandId = $commandId;
        $this->summary = $summary;
        $this->endTime = $endTime;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('test-execution.' . $this->commandId),
        ];
    }

    /**
     * Get the data to broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'command_id' => $this->commandId,
            'summary' => $this->summary,
            'end_time' => $this->endTime->toISOString(),
            'status' => 'completed',
            'metadata' => $this->metadata,
        ];
    }

    /**
     * The event's broadcast name
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'test.execution.completed';
    }

    /**
     * Determine if the overall execution was successful
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return ($this->summary['status'] ?? 'failed') === 'passed';
    }

    /**
     * Get the total number of tests run
     *
     * @return int
     */
    public function getTotalTests(): int
    {
        return $this->summary['total_tests'] ?? 0;
    }

    /**
     * Get the number of passed tests
     *
     * @return int
     */
    public function getPassedTests(): int
    {
        return $this->summary['total_passed'] ?? 0;
    }

    /**
     * Get the number of failed tests
     *
     * @return int
     */
    public function getFailedTests(): int
    {
        return $this->summary['total_failed'] ?? 0;
    }

    /**
     * Get the number of skipped tests
     *
     * @return int
     */
    public function getSkippedTests(): int
    {
        return $this->summary['total_skipped'] ?? 0;
    }

    /**
     * Get the success rate percentage
     *
     * @return float
     */
    public function getSuccessRate(): float
    {
        return $this->summary['success_rate'] ?? 0.0;
    }

    /**
     * Get the total execution duration
     *
     * @return float
     */
    public function getTotalDuration(): float
    {
        return $this->summary['total_duration'] ?? 0.0;
    }

    /**
     * Get formatted duration string
     *
     * @return string
     */
    public function getFormattedDuration(): string
    {
        $duration = $this->getTotalDuration();
        
        if ($duration < 1) {
            return number_format($duration, 2) . 's';
        } elseif ($duration < 60) {
            return number_format($duration, 1) . 's';
        } else {
            $minutes = floor($duration / 60);
            $seconds = $duration % 60;
            return $minutes . 'm ' . number_format($seconds, 1) . 's';
        }
    }

    /**
     * Get test failures
     *
     * @return array
     */
    public function getFailures(): array
    {
        return $this->summary['failures'] ?? [];
    }

    /**
     * Get test warnings
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->summary['warnings'] ?? [];
    }

    /**
     * Determine if there are any failures
     *
     * @return bool
     */
    public function hasFailures(): bool
    {
        return !empty($this->getFailures());
    }

    /**
     * Determine if there are any warnings
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->getWarnings());
    }

    /**
     * Get execution status summary
     *
     * @return string
     */
    public function getStatusSummary(): string
    {
        $total = $this->getTotalTests();
        $passed = $this->getPassedTests();
        $failed = $this->getFailedTests();
        $skipped = $this->getSkippedTests();

        if ($total === 0) {
            return 'No tests executed';
        }

        $parts = [];
        
        if ($passed > 0) {
            $parts[] = "{$passed} passed";
        }
        
        if ($failed > 0) {
            $parts[] = "{$failed} failed";
        }
        
        if ($skipped > 0) {
            $parts[] = "{$skipped} skipped";
        }

        return implode(', ', $parts) . " ({$total} total)";
    }

    /**
     * Get a short status indicator
     *
     * @return string
     */
    public function getStatusIndicator(): string
    {
        if ($this->wasSuccessful()) {
            return '✅ PASSED';
        } elseif ($this->getFailedTests() > 0) {
            return '❌ FAILED';
        } else {
            return '⚠️  WARNING';
        }
    }
}