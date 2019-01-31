<?php

namespace App\Response;

use App\Model\Ai;

class GetAiResponse extends Response
{
    /**
     * @var Ai
     */
    private $ai;

    /**
     * @return Ai
     */
    public function getAi(): Ai
    {
        return $this->ai;
    }

    /**
     * @param Ai $ai
     *
     * @return GetAiResponse
     */
    public function setAi(Ai $ai)
    {
        $this->ai = $ai;

        return $this;
    }
}
