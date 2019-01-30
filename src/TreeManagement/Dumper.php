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
    private $dir;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    public function __construct(string $dir, string $projectDir)
    {
        $this->dir = substr($dir, 0, 1) === '/' ? $dir : "$projectDir/$dir";
        $this->fileSystem = new Filesystem();
    }

    public function dump(Folder $folder, $parentPath = '')
    {
        $path = 0 !== $folder->getId() ? "$parentPath/{$folder->getName()}" : '';

        $this->createDir($path);

        foreach ($folder->getFolders() as $child) {
            $this->dump($child, $path);
        }

        foreach ($folder->getAis() as $ai) {
            $this->createFile($ai, $path);
        }
    }

    private function createDir(string $path)
    {
        $path = "{$this->dir}{$path}";

        $this->fileSystem->mkdir($path);
    }

    private function createFile(Ai $ai, string $path)
    {
        $path = "{$this->dir}{$path}/{$ai->getName()}.lks";

        $this->fileSystem->dumpFile($path, $ai->getCode());
    }
}
