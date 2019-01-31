<?php

namespace App\TreeManagement;

class ScriptsDirResolver
{
    private $scriptsDir;

    public function __construct(string $projectDir)
    {
        $scriptsDirPath = getenv('APP_SCRIPTS_DIR');

        $separator = DIRECTORY_SEPARATOR;

        // @TODO Handle Absolute path on Windows
        if (!$scriptsDirPath) {
            $this->scriptsDir = $projectDir.DIRECTORY_SEPARATOR.'scripts';
        } elseif (preg_match("/^\\{$separator}/", $scriptsDirPath)) {
            $this->scriptsDir = $scriptsDirPath;
        } else {
            $this->scriptsDir = $projectDir.DIRECTORY_SEPARATOR.$scriptsDirPath;
        }
    }

    /**
     * @return mixed
     */
    public function getScriptsDir()
    {
        return $this->scriptsDir;
    }
}
