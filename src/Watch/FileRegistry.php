<?php

namespace App\Watch;

use App\Model\Ai;
use App\Model\Folder;
use App\TreeManagement\Builder;
use DusanKasan\Knapsack\Exceptions\ItemNotFound;

class FileRegistry
{
    /**
     * @var Ai[]
     */
    private $ais;

    /**
     * @var Folder[]
     */
    private $folders;

    /**
     * @var string
     */
    private $scriptsDir;

    /**
     * @var string
     */
    private $extension;

    public function __construct(string $scriptsDir)
    {
        $this->scriptsDir = $scriptsDir;
        $this->extension = getenv('APP_FILE_EXTENSION');
        $this->ais = [];
        $this->folders = [];
    }

    public function init(Folder $tree)
    {
        foreach (Builder::flattenAis($tree) as $ai) {
            $this->ais[$this->getAiPath($ai)] = $ai;
        }

        foreach (Builder::flattenFolders($tree) as $folder) {
            $this->folders[$this->getFolderPath($folder)] = $folder;
        }
    }

    public function hasAi(string $path): bool
    {
        return isset($this->ais[$path]);
    }

    public function fetchAi(string $path): Ai
    {
        if (!$this->hasAi($path)) {
            throw new \Exception("Unable to find AI $path");
        }

        return $this->ais[$path];
    }

    public function findAiById(int $getErroredAiId): ?Ai
    {
        foreach ($this->ais as $ai) {
            if ($ai->getId() === $getErroredAiId) {
                return $ai;
            }
        }

        return null;
    }

    public function pushAi(Ai $ai)
    {
        $this->ais[$this->getAiPath($ai)] = $ai;

        return $this;
    }

    public function deleteAi(Ai $ai)
    {
        $path = $this->getAiPath($ai);

        if ($this->hasAi($path)) {
            unset($this->ais[$path]);
        }

        return $this;
    }

    public function moveAi(Ai $ai, string $fromPath)
    {
        if ($this->hasAi($fromPath)) {
            unset($this->ais[$fromPath]);
        }

        $this->pushAi($ai);

        return $this;
    }

    public function hasFolder(string $path): bool
    {
        return isset($this->folders[$path]);
    }

    public function fetchFolder(string $path): Folder
    {
        if (!$this->hasFolder($path)) {
            throw new \Exception("Unable to find Folder $path");
        }

        return $this->folders[$path];
    }

    public function pushFolder(Folder $folder)
    {
        $this->folders[$this->getFolderPath($folder)] = $folder;

        return $this;
    }

    public function deleteFolder(Folder $folder)
    {
        $path = $this->getFolderPath($folder);

        if ($this->hasFolder($path)) {
            unset($this->folders[$path]);
        }

        return $this;
    }

    public function moveFolder(Folder $folder, string $fromPath)
    {
        if ($this->hasFolder($fromPath)) {
            unset($this->folders[$fromPath]);
        }

        $this->pushFolder($folder);

        return $this;
    }

    private function getAiPath(Ai $ai): string
    {
        return "{$this->scriptsDir}{$ai->getPath()}.{$this->extension}";
    }

    private function getFolderPath(Folder $folder): string
    {
        return "{$this->scriptsDir}{$folder->getPath()}";
    }
}
