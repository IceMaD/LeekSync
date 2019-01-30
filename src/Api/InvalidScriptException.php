<?php

namespace App\Api;

class InvalidScriptException extends \Exception
{
    /**
     * @var string
     */
    private $position;

    /**
     * @var string
     */
    private $error;

    public function __construct(int $line, int $column, string $error)
    {
        parent::__construct('', 0, null);

        $this->position = "$line:$column";
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}
