<?php

namespace App\Response;

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

    public function getError()
    {
        if ($this->isAiValid()) {
            return null;
        }

        [,,, $line, $column,, $error] = $this->result;

        return ['line' => $line, 'column' => $column, 'error' => $error];
    }
}
