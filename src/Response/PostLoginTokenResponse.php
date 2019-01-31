<?php

namespace App\Response;

use App\Model\Farmer;

class PostLoginTokenResponse extends Response
{
    /**
     * @var Farmer
     */
    private $farmer;

    /**
     * @var string
     */
    private $token;

    /**
     * @return Farmer
     */
    public function getFarmer(): Farmer
    {
        return $this->farmer;
    }

    /**
     * @param Farmer $farmer
     *
     * @return PostLoginTokenResponse
     */
    public function setFarmer(Farmer $farmer)
    {
        $this->farmer = $farmer;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return PostLoginTokenResponse
     */
    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }
}
