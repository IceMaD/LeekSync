<?php

namespace App\Watch;

class Watcher extends \JasonLewis\ResourceWatcher\Watcher
{
    /**
     * @var string
     */
    private $scriptsDir;

    public function __construct(Tracker $tracker, Filesystem $files, string $scriptsDir)
    {
        parent::__construct($tracker, $files);

        $this->scriptsDir = $scriptsDir;
    }

    public function watch($forCompatibility = null)
    {
        return parent::watch($this->scriptsDir);
    }
}
