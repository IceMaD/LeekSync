<?php

namespace App\TreeManagement;

use App\Model\Ai;

class ConflictException extends \Exception
{
    /**
     * @var \Diff
     */
    private $diff;

    /**
     * @var string
     */
    private $path;

    public function __construct(string $code, Ai $ai, string $path)
    {
        parent::__construct('', 0, null);

        $this->diff = new \Diff(
            explode("\n", $code),
            explode("\n", $ai->getCode())
        );
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getDiffView()
    {
        return $this->diff->render(new \Diff_Renderer_Text_Unified());
    }
}
