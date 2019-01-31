<?php

namespace App\Response;

abstract class Response
{
    /**
     * @var bool
     */
    protected $success;

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     *
     * @return Response
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;

        return $this;
    }
}
