<?php

namespace App\TreeManagement;

use App\Model\Ai;
use App\Model\Folder;
use Symfony\Component\Filesystem\Filesystem;

class Dumper
{
    /**
     * @var string
     */
    private $scriptsDir;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    public function __construct(string $scriptsDir)
    {
        $this->scriptsDir = $scriptsDir;
        $this->fileSystem = new Filesystem();
    }

    /**
     * @throws ConflictException
     */
    public function dump(Folder $folder)
    {
        $this->createDir($folder);

        foreach ($folder->getFolders() as $child) {
            $this->dump($child);
        }

        foreach ($folder->getAis() as $ai) {
            $this->createFile($ai);
        }
    }

    private function createDir(Folder $folder)
    {
        $this->fileSystem->mkdir("{$this->scriptsDir}{$folder->getPath()}");
    }

    /**
     * @throws ConflictException
     */
    private function createFile(Ai $ai)
    {
        $path = "{$this->scriptsDir}{$ai->getPath()}";

        if (!$this->fileSystem->exists($path)) {
            $this->fileSystem->dumpFile($path, $ai->getCode());

            return;
        }

        $code = file_get_contents($path);

        if ($code === $ai->getCode()) {
            return;
        }

        throw new ConflictException($code, $ai, $path);
    }
}
