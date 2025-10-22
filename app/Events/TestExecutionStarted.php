<?php

namespace App\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestExecutionStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique command identifier
     */
    public string $commandId;

    /**
     * Number of tests to be executed
     */
    public int $testCount;

    /**
     * Execution start time
     */
    public Carbon $startTime;

    /**
     * Additional execution metadata
     */
    public array $metadata;

    /**
     * Create a new event instance
     *
     * @param  string  $commandId  Unique command identifier
     * @param  int  $testCount  Number of tests to execute
     * @param  Carbon  $startTime  When execution started
     * @param  array  $metadata  Additional execution metadata
     */
    public function __construct(string $commandId, int $testCount, Carbon $startTime, array $metadata = [])
    {
        $this->commandId = $commandId;
        $this->testCount = $testCount;
        $this->startTime = $startTime;
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
            new PrivateChannel('test-execution.'.$this->commandId),
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'command_id' => $this->commandId,
            'test_count' => $this->testCount,
            'start_time' => $this->startTime->toISOString(),
            'status' => 'started',
            'metadata' => $this->metadata,
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'test.execution.started';
    }
}
