<?php

namespace App\Exceptions;

use Throwable;

class TestExecutionException extends \RuntimeException
{
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_TEST = 'test';
    public const CATEGORY_CONFIGURATION = 'configuration';

    /**
     * @var string
     */
    protected string $category;

    /**
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * @param string $message
     * @param string $category
     * @param array<string, mixed> $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        string $category = self::CATEGORY_SYSTEM,
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->category = $category;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}

