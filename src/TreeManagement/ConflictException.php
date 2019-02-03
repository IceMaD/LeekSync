<?php

namespace App\TreeManagement;

use IceMaD\LeekWarsApiBundle\Entity\Ai;

class ConflictException extends \Exception
{
    /**
     * @var \Diff
     */
    private $diff;

    /**
     * @var Ai
     */
    private $ai;

    public function __construct(string $code, Ai $ai)
    {
        parent::__construct('', 0, null);

        $this->diff = new \Diff(
            explode("\n", $ai->getCode()),
            explode("\n", $code)
        );
        $this->ai = $ai;
    }

    /**
     * @return string
     */
    public function getDiffView()
    {
        return $this->diff->render(new \Diff_Renderer_Text_Unified());
    }

    /**
     * @return Ai
     */
    public function getAi(): Ai
    {
        return $this->ai;
    }
}
