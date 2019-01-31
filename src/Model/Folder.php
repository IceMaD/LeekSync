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
     * @return string|null
     */
    public function getPath(): ?string
    {
        if (0 === $this->id) {
            return '';
        }

        if (!$this->folder instanceof Folder) {
            return null;
        }

        return $this->folder->getPath().DIRECTORY_SEPARATOR.$this->getName();
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
        $folder->setFolder($this);

        return $this;
    }

    public function removeFolder(Folder $folderToRemove)
    {
        $this->folders = array_filter($this->folders, function (Folder $folder) use ($folderToRemove) {
            return $folder->getId() !== $folderToRemove->getId();
        });

        return $this;
    }

    public function addAi(Ai $ai)
    {
        $this->ais[] = $ai;
        $ai->setFolder($this);

        return $this;
    }

    public function removeAi(Ai $aiToRemove)
    {
        $this->ais = array_filter($this->ais, function (Ai $ai) use ($aiToRemove) {
            return $ai->getId() !== $aiToRemove->getId();
        });

        return $this;
    }
}
