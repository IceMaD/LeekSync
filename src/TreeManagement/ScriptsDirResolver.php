<?php

namespace App\TreeManagement;

class ScriptsDirResolver
{
    private $scriptsDir;

    public function __construct(string $projectDir)
    {
        $scriptsDirPath = getenv('APP_SCRIPTS_DIR');

        if (!$scriptsDirPath) {
            $this->scriptsDir = "$projectDir/scripts";
        } elseif (preg_match('/^\//', $scriptsDirPath)) {
            $this->scriptsDir = $scriptsDirPath;
        } else {
            $this->scriptsDir = "$projectDir/$scriptsDirPath";
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
