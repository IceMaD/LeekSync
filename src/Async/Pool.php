<?php

namespace App\Async;

use Spatie\Async\Output\ParallelError;
use Spatie\Async\Process\ParallelProcess;

class Pool extends \Spatie\Async\Pool
{
    /**
     * @var ParallelProcess[]
     */
    private $stopped = [];

    public function isRunning()
    {
        return count($this->inProgress) > 0;
    }

    public function stop()
    {
        $this->stopped = [];

        foreach ($this->inProgress as $runnable) {
            try {
                $this->stopped[] = $runnable;
                $runnable->stop();
            } catch (ParallelError $error) {
                // Nullify ParallelError as the stop is intended
            }
        }
    }
}
