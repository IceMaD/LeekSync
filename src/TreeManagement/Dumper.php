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

    /**
     * @var string
     */
    private $extension;

    public function __construct(string $scriptsDir)
    {
        $this->scriptsDir = $scriptsDir;
        $this->extension = getenv('APP_FILE_EXTENSION');
        $this->fileSystem = new Filesystem();
    }

    public function dump(Folder $folder, bool $force)
    {
        $ais = [
            'fetched' => [],
            'conflicts' => [],
        ];

        $this->createDir($folder);

        foreach ($folder->getFolders() as $child) {
            $ais = array_merge_recursive($ais, $this->dump($child, $force));
        }

        foreach ($folder->getAis() as $ai) {
            try {
                $this->createFile($ai, $force);
                $ais['fetched'][] = $ai;
            } catch (ConflictException $exception) {
                $ais['conflicts'][] = $exception;
            }
        }

        return $ais;
    }

    public function createDir(Folder $folder)
    {
        $this->fileSystem->mkdir("{$this->scriptsDir}{$folder->getPath()}");
    }

    public function createFile(Ai $ai, bool $force)
    {
        $path = "{$this->scriptsDir}{$ai->getPath()}.{$this->extension}";

        if (!$this->fileSystem->exists($path) || $force) {
            $this->fileSystem->dumpFile($path, $ai->getCode());

            return;
        }

        $code = file_get_contents($path);

        if ($code === $ai->getCode()) {
            return;
        }

        throw new ConflictException($code, $ai);
    }
}
