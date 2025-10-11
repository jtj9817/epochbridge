<?php

namespace App\Contracts;

use Symfony\Component\Process\Process;

interface TestProcessFactory
{
    /**
     * Create a new Symfony Process instance for the given command.
     *
     * @param string $command
     * @param string|null $workingDirectory
     * @return \Symfony\Component\Process\Process
     */
    public function make(string $command, ?string $workingDirectory = null): Process;
}
