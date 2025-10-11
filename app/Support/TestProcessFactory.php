<?php

namespace App\Support;

use App\Contracts\TestProcessFactory as TestProcessFactoryContract;
use Symfony\Component\Process\Process;

class TestProcessFactory implements TestProcessFactoryContract
{
    /**
     * {@inheritdoc}
     */
    public function make(string $command, ?string $workingDirectory = null): Process
    {
        $cwd = $workingDirectory ?? base_path();

        return Process::fromShellCommandline($command, $cwd);
    }
}
