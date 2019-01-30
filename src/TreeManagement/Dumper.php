<?php

namespace App\TreeManagement;

use App\Model\Ai;
use App\Model\Folder;
use Diff;
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

    /**
     * @throws ConflictException
     */
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

    /**
     * @throws ConflictException
     */
    private function createFile(Ai $ai, string $path)
    {
        $path = "{$this->dir}{$path}/{$ai->getName()}.lks";

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
