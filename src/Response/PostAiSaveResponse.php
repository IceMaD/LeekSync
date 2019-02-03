<?php

namespace App\Response;

use App\Api\InvalidScriptError;

class PostAiSaveResponse extends Response
{
    /**
     * @var array
     */
    private $result;

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     *
     * @return PostAiSaveResponse
     */
    public function setResult(array $result)
    {
        $this->result = $result[0];

        return $this;
    }

    public function isAiValid()
    {
        return 3 === count($this->result);
    }

    public function getAiError(): InvalidScriptError
    {
        if ($this->isAiValid()) {
            return null;
        }

        return new InvalidScriptError($this->result);
    }
}
