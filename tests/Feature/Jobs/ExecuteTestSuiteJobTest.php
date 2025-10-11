<?php

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use App\Events\TestExecutionStarted;
use App\Contracts\TestProcessFactory;
use App\Jobs\ExecuteTestSuiteJob;
use App\Services\ErrorRecoveryService;
use App\Services\MarkdownReportService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Mockery\MockInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

afterEach(function () {
    m::close();
});

it('dispatches execute job with expected payload and defaults', function () {
    Bus::fake();

    $files = ['tests/Unit/FooTest.php', 'tests/Feature/BarTest.php'];

    ExecuteTestSuiteJob::dispatch($files, 'reports/run.md', 'cmd-queue-1', ['parallel' => false]);

    Bus::assertDispatched(ExecuteTestSuiteJob::class, function (ExecuteTestSuiteJob $job) use ($files) {
        return $job->testFiles === $files
            && $job->outputFile === 'reports/run.md'
            && $job->commandId === 'cmd-queue-1'
            && $job->options['parallel'] === false
            && $job->tries === 3
            && $job->maxExceptions === 3;
    });
});

it('runs selected tests, emits events, and builds summary', function () {
    Event::fake([
        TestExecutionStarted::class,
        TestCompleted::class,
        TestExecutionCompleted::class,
    ]);

    $markdown = $this->mock(MarkdownReportService::class, function (MockInterface $mock) {
        $mock->shouldReceive('initializeReport')->once();
    });

    $recovery = $this->mock(ErrorRecoveryService::class, function (MockInterface $mock) {
        $mock->shouldReceive('handleTestFailure')->never();
    });

    $processMock = m::mock(Process::class);
    $processMock->shouldReceive('setTimeout')->twice()->andReturnNull();
    $processMock->shouldReceive('run')->twice()->andReturnNull();
    $processMock->shouldReceive('getOutput')->twice()->andReturn("Tests: 1 passed\nAssertions: 2");
    $processMock->shouldReceive('getErrorOutput')->twice()->andReturn('');
    $processMock->shouldReceive('getExitCode')->twice()->andReturn(0);

    $this->mock(TestProcessFactory::class, function (MockInterface $mock) use ($processMock) {
        $mock->shouldReceive('make')
            ->twice()
            ->withArgs(function (string $command, ?string $cwd) {
                return str_starts_with($command, 'php artisan test') && $cwd === base_path();
            })
            ->andReturn($processMock);
    });

    $job = new ExecuteTestSuiteJob(
        [
            ['file_path' => 'tests/Unit/FooTest.php'],
            ['file_path' => 'tests/Feature/BarTest.php'],
        ],
        'test-report.md',
        'cmd-handle-1',
        ['parallel' => true, 'processes' => 2]
    );

    $job->handle();

    Event::assertDispatched(TestExecutionStarted::class);
    Event::assertDispatchedTimes(TestCompleted::class, 2);
    Event::assertDispatched(TestExecutionCompleted::class, function ($event) {
        return $event->summary['status'] === 'passed'
            && $event->commandId === 'cmd-handle-1';
    });
});

it('invokes recovery service when process exits with failure', function () {
    Event::fake();

    $markdown = $this->mock(MarkdownReportService::class, function (MockInterface $mock) {
        $mock->shouldReceive('initializeReport')->once();
    });

    $recovery = $this->mock(ErrorRecoveryService::class);
    $recovery->shouldReceive('handleTestFailure')->once()->andReturn([
        'retry' => false,
        'delay' => null,
    ]);

    $processMock = m::mock(Process::class);
    $processMock->shouldReceive('setTimeout')->once()->andReturnNull();
    $processMock->shouldReceive('run')->once()->andReturnNull();
    $processMock->shouldReceive('getOutput')->once()->andReturn('');
    $processMock->shouldReceive('getErrorOutput')->once()->andReturn('Failure');
    $processMock->shouldReceive('getExitCode')->once()->andReturn(1);

    $this->mock(TestProcessFactory::class, function (MockInterface $mock) use ($processMock) {
        $mock->shouldReceive('make')
            ->once()
            ->withArgs(function (string $command, ?string $cwd) {
                return str_starts_with($command, 'php artisan test') && $cwd === base_path();
            })
            ->andReturn($processMock);
    });

    $job = new ExecuteTestSuiteJob(
        [['file_path' => 'tests/Feature/FlakyTest.php']],
        'fail-report.md',
        'cmd-fail-1',
        ['retry_attempts' => 0]
    );

    $job->handle();

    Event::assertDispatched(TestExecutionCompleted::class, function ($event) {
        return $event->summary['status'] === 'failed';
    });
});

it('retries on process timeouts and stops after maximum attempts', function () {
    Event::fake();

    $markdown = $this->mock(MarkdownReportService::class, function (MockInterface $mock) {
        $mock->shouldReceive('initializeReport')->once();
    });

    $recovery = $this->mock(ErrorRecoveryService::class);
    $recovery->shouldReceive('handleTestFailure')->twice()->andReturn([
        'retry' => true,
        'delay' => 0,
    ]);

    $processMock = m::mock(Process::class);
    $processMock->shouldReceive('setTimeout')->andReturnNull();
    $processMock->shouldReceive('run')->andThrow(new ProcessTimedOutException(new \Symfony\Component\Process\Process(['php']), ProcessTimedOutException::TYPE_GENERAL));

    $this->mock(TestProcessFactory::class, function (MockInterface $mock) use ($processMock) {
        $mock->shouldReceive('make')
            ->twice()
            ->withArgs(function (string $command, ?string $cwd) {
                return str_starts_with($command, 'php artisan test') && $cwd === base_path();
            })
            ->andReturn($processMock);
    });

    $job = new ExecuteTestSuiteJob(
        [['file_path' => 'tests/Feature/TimeoutTest.php']],
        'timeout-report.md',
        'cmd-timeout-1',
        ['retry_attempts' => 1]
    );

    expect(function () use ($job) {
        $job->handle();
    })->toThrow(ProcessTimedOutException::class);
});

it('records permanent failure via failed handler', function () {
    Event::fake([TestExecutionCompleted::class]);

    $recovery = $this->mock(ErrorRecoveryService::class);
    $recovery->shouldReceive('handleTestFailure')->once();

    $job = new ExecuteTestSuiteJob([], 'report.md', 'cmd-failed-handler');

    $job->failed(new \RuntimeException('boom'));

    Event::assertDispatched(TestExecutionCompleted::class, function ($event) {
        return $event->summary['status'] === 'error'
            && $event->commandId === 'cmd-failed-handler';
    });
});
