<?php

namespace App\Api;

class InvalidScriptError
{
    /**
     * @var int
     */
    private $successCode;

    /**
     * @var int
     */
    private $aiId;

    /**
     * @var int
     */
    private $erroredAiId;

    /**
     * @var int
     */
    private $line;

    /**
     * @var int
     */
    private $column;

    /**
     * @var string
     */
    private $character;

    /**
     * @var string
     */
    private $error;

    public function __construct(array $result)
    {
        [
            $this->successCode,
            $this->aiId,
            $this->erroredAiId,
            $this->line,
            $this->column,
            $this->character,
            $this->error,
        ] = $result;
    }

    /**
     * @return int
     */
    public function getSuccessCode(): int
    {
        return $this->successCode;
    }

    /**
     * @return int
     */
    public function getAiId(): int
    {
        return $this->aiId;
    }

    /**
     * @return int
     */
    public function getErroredAiId(): int
    {
        return $this->erroredAiId;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getColumn(): int
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getCharacter(): string
    {
        return $this->character;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}
