<?php

namespace App\Contracts;

use Symfony\Component\Process\Process;

interface TestProcessFactory
{
    /**
     * Create a new Symfony Process instance for the given command.
     */
    public function make(string $command, ?string $workingDirectory = null): Process;
}
