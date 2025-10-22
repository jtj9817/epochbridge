<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The test file that was executed
     */
    public string $testFile;

    /**
     * Test execution status (passed, failed, skipped, error)
     */
    public string $status;

    /**
     * Test execution duration in seconds
     */
    public float $duration;

    /**
     * Test output
     */
    public string $output;

    /**
     * Test errors (if any)
     */
    public string $errors;

    /**
     * Additional test metadata
     */
    public array $metadata;

    /**
     * Create a new event instance
     *
     * @param  string  $testFile  Path to the test file
     * @param  string  $status  Test execution status
     * @param  float  $duration  Execution duration in seconds
     * @param  string  $output  Test output
     * @param  string  $errors  Test errors
     * @param  array  $metadata  Additional metadata
     */
    public function __construct(
        string $testFile,
        string $status,
        float $duration,
        string $output = '',
        string $errors = '',
        array $metadata = []
    ) {
        $this->testFile = $testFile;
        $this->status = $status;
        $this->duration = $duration;
        $this->output = $output;
        $this->errors = $errors;
        $this->metadata = array_merge([
            'completed_at' => now()->toISOString(),
        ], $metadata);
    }

    /**
     * Get the channels the event should broadcast on
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('test-execution'),
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'test_file' => $this->testFile,
            'status' => $this->status,
            'duration' => $this->duration,
            'output' => $this->output,
            'errors' => $this->errors,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'test.completed';
    }

    /**
     * Determine if the test passed
     */
    public function hasPassed(): bool
    {
        return $this->status === 'passed';
    }

    /**
     * Determine if the test failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, ['failed', 'error']);
    }

    /**
     * Determine if the test was skipped
     */
    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Get a human-readable test name
     */
    public function getTestName(): string
    {
        return basename($this->testFile, '.php');
    }

    /**
     * Get the test file relative path
     */
    public function getRelativePath(): string
    {
        $basePath = base_path('tests/');
        if (str_starts_with($this->testFile, $basePath)) {
            return str_replace($basePath, '', $this->testFile);
        }

        return $this->testFile;
    }

    /**
     * Format duration for human reading
     */
    public function getFormattedDuration(): string
    {
        if ($this->duration < 0.01) {
            return '< 0.01s';
        } elseif ($this->duration < 1) {
            return number_format($this->duration, 2).'s';
        } elseif ($this->duration < 60) {
            return number_format($this->duration, 1).'s';
        } else {
            $minutes = floor($this->duration / 60);
            $seconds = $this->duration % 60;

            return $minutes.'m '.number_format($seconds, 1).'s';
        }
    }
}
