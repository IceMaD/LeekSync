<?php

namespace App\Model;

class Folder
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int|Folder
     */
    private $folder;

    /**
     * @var Folder[]
     */
    private $folders = [];

    /**
     * @var Ai[]
     */
    private $ais = [];

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Folder
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Folder
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Folder|int
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param Folder|int $folder
     *
     * @return Folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return Folder[]
     */
    public function getFolders(): array
    {
        return $this->folders;
    }

    /**
     * @param Folder[] $folders
     *
     * @return Folder
     */
    public function setFolders(array $folders)
    {
        $this->folders = $folders;

        return $this;
    }

    /**
     * @return Ai[]
     */
    public function getAis(): array
    {
        return $this->ais;
    }

    /**
     * @param Ai[] $ais
     *
     * @return Folder
     */
    public function setAis(array $ais)
    {
        $this->ais = $ais;

        return $this;
    }

    public function addFolder(Folder $folder)
    {
        $this->folders[] = $folder;

        return $this;
    }

    public function addAi(Ai $ai)
    {
        $this->ais[] = $ai;

        return $this;
    }
}
