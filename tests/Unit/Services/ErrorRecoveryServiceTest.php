<?php

use App\Exceptions\TestExecutionException;
use App\Services\ErrorRecoveryService;

uses(Tests\TestCase::class);

describe('ErrorRecoveryService', function () {
    beforeEach(function () {
        $this->service = app(ErrorRecoveryService::class);
    });

    it('diagnoses known test execution exceptions by category', function () {
        $exception = new TestExecutionException(
            'Assertion failed',
            TestExecutionException::CATEGORY_TEST,
            ['file' => 'tests/Feature/FooTest.php']
        );

        $diagnosis = $this->service->diagnoseError($exception, ['attempt' => 1]);

        expect($diagnosis)
            ->toHaveKey('category', TestExecutionException::CATEGORY_TEST)
            ->and($diagnosis['action'])->toBe('inspect_failing_test')
            ->and($diagnosis['context'])->toBe(['attempt' => 1]);
    });

    it('diagnoses configuration and system errors based on message heuristics', function () {
        $configuration = new \RuntimeException('Configuration mismatch detected.');
        $system = new \RuntimeException('Out of memory error');

        $configDiagnosis = $this->service->diagnoseError($configuration);
        $systemDiagnosis = $this->service->diagnoseError($system);

        expect($configDiagnosis['category'])->toBe(TestExecutionException::CATEGORY_CONFIGURATION)
            ->and($systemDiagnosis['category'])->toBe(TestExecutionException::CATEGORY_SYSTEM);
    });

    it('determines recovery strategy based on category and attempt count', function () {
        $transient = $this->service->attemptRecovery(
            ['category' => TestExecutionException::CATEGORY_SYSTEM],
            ['attempt' => 1]
        );

        $permanent = $this->service->attemptRecovery(
            ['category' => TestExecutionException::CATEGORY_TEST],
            ['attempt' => 1]
        );

        $configuration = $this->service->attemptRecovery(
            ['category' => TestExecutionException::CATEGORY_CONFIGURATION],
            ['attempt' => 2]
        );

        expect($transient['retry'])->toBeTrue()
            ->and($transient['delay'])->toBe(2)
            ->and($permanent['retry'])->toBeFalse()
            ->and($permanent['message'])->toContain('assertion')
            ->and($configuration['retry'])->toBeFalse();
    });

    it('caps retry attempts and delay duration for repeated failures', function () {
        $retry = $this->service->attemptRecovery(
            ['category' => TestExecutionException::CATEGORY_SYSTEM],
            ['attempt' => 4]
        );

        expect($retry['retry'])->toBeFalse()
            ->and($retry['delay'])->toBe(16);

        $capped = $this->service->attemptRecovery(
            ['category' => TestExecutionException::CATEGORY_SYSTEM],
            ['attempt' => 7]
        );

        expect($capped['delay'])->toBe(60);
    });

    it('wraps diagnosis and recovery details in handleTestFailure output', function () {
        $exception = new \RuntimeException('Transient error occurred');

        $result = $this->service->handleTestFailure('cmd-123', ['attempt' => 2], $exception);

        expect($result)
            ->toHaveKey('diagnosis')
            ->toHaveKey('recovery')
            ->toHaveKey('retry')
            ->and($result['recovery']['retry'])->toBeTrue();
    });
});
