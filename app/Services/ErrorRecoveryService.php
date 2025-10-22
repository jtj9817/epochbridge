<?php

namespace App\Services;

use App\Exceptions\TestExecutionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorRecoveryService
{
    /**
     * Handle a test failure and determine recovery strategy.
     *
     * @param  array<string, mixed>  $testContext
     * @return array<string, mixed>
     */
    public function handleTestFailure(string $commandId, array $testContext, Throwable $exception): array
    {
        $diagnosis = $this->diagnoseError($exception, $testContext);

        Log::warning('Test execution failure detected', [
            'command_id' => $commandId,
            'test_context' => $testContext,
            'diagnosis' => $diagnosis,
            'exception' => $exception->getMessage(),
        ]);

        $recovery = $this->attemptRecovery($diagnosis, $testContext);

        if ($recovery['retry'] ?? false) {
            Log::info('Scheduling retry for failed test', [
                'command_id' => $commandId,
                'test_context' => $testContext,
                'delay' => $recovery['delay'] ?? 0,
            ]);
        }

        return [
            'diagnosis' => $diagnosis,
            'recovery' => $recovery,
            'retry' => $recovery['retry'] ?? false,
            'delay' => $recovery['delay'] ?? null,
        ];
    }

    /**
     * Diagnose the failure and classify it.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function diagnoseError(Throwable $exception, array $context = []): array
    {
        $category = TestExecutionException::CATEGORY_SYSTEM;
        $reason = $exception->getMessage();

        if ($exception instanceof TestExecutionException) {
            $category = $exception->getCategory();
            $reason = $exception->getMessage();
        } elseif (str_contains(strtolower($exception->getMessage()), 'configuration')) {
            $category = TestExecutionException::CATEGORY_CONFIGURATION;
        } elseif (str_contains(strtolower($exception->getMessage()), 'assertion')) {
            $category = TestExecutionException::CATEGORY_TEST;
        }

        $action = match ($category) {
            TestExecutionException::CATEGORY_CONFIGURATION => 'verify_configuration',
            TestExecutionException::CATEGORY_TEST => 'inspect_failing_test',
            default => 'retry',
        };

        return [
            'category' => $category,
            'reason' => $reason,
            'action' => $action,
            'context' => $context,
        ];
    }

    /**
     * Attempt recovery and determine whether a retry should occur.
     *
     * @param  array<string, mixed>  $diagnosis
     * @param  array<string, mixed>  $testContext
     * @return array<string, mixed>
     */
    public function attemptRecovery(array $diagnosis, array $testContext): array
    {
        $category = $diagnosis['category'] ?? TestExecutionException::CATEGORY_SYSTEM;
        $attempt = (int) ($testContext['attempt'] ?? 1);

        // Exponential backoff with a sensible ceiling.
        $delaySeconds = min(60, 2 ** ($attempt));

        return match ($category) {
            TestExecutionException::CATEGORY_TEST => [
                'retry' => false,
                'delay' => null,
                'message' => 'Test failed due to assertion or expectation failure.',
            ],
            TestExecutionException::CATEGORY_CONFIGURATION => [
                'retry' => false,
                'delay' => null,
                'message' => 'Configuration issue detected. Manual intervention required.',
            ],
            default => [
                'retry' => ($attempt <= 3),
                'delay' => $delaySeconds,
                'message' => 'Transient system issue detected. Retrying after backoff.',
            ],
        };
    }
}
