<?php

namespace App\Api;

class TokenStorage
{
    /**
     * @var string|null
     */
    private $token;

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     *
     * @return TokenStorage
     */
    public function setToken(?string $token)
    {
        $this->token = $token;

        return $this;
    }
}
