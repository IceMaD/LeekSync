<?php

namespace App\Response;

class PostFolderNewResponse extends Response
{
    /**
     * @var int
     */
    private $id;

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
     * @return PostFolderNewResponse
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }
}
