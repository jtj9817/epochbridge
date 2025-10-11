<?php

namespace Tests\Support;

class PromptSequence
{
    /**
     * @var array<int, mixed>
     */
    private static array $values = [];

    public static function reset(): void
    {
        self::$values = [];
    }

    /**
     * @param array<int, mixed> $values
     */
    public static function seed(array $values): void
    {
        self::$values = $values;
    }

    public static function pull(mixed $default = null): mixed
    {
        return array_shift(self::$values) ?? $default;
    }
}
