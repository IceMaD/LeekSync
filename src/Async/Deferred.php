<?php

namespace App\Async;

class Deferred
{
    /**
     * @var \Spatie\Async\Process\ParallelProcess|\Spatie\Async\Process\Runnable
     */
    private $process;

    public function __construct(callable $callback)
    {
        $this->process = async(function () {
            usleep(1);

            return true;
        })->then($callback);
    }

    /**
     * @return \Spatie\Async\Process\ParallelProcess|\Spatie\Async\Process\Runnable
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param \Spatie\Async\Process\ParallelProcess|\Spatie\Async\Process\Runnable $process
     *
     * @return Deferred
     */
    public function setProcess($process)
    {
        $this->process = $process;

        return $this;
    }

    public function stop(): self
    {
        $this->process->stop();

        return $this;
    }

    public function isRunning()
    {
        return $this->process->isRunning();
    }
}
