<?php

namespace App\Exceptions;

use Throwable;

class TestExecutionException extends \RuntimeException
{
    public const CATEGORY_SYSTEM = 'system';

    public const CATEGORY_TEST = 'test';

    public const CATEGORY_CONFIGURATION = 'configuration';

    protected string $category;

    /**
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * @param  array<string, mixed>  $context
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
