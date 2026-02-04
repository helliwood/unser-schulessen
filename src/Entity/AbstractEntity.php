<?php

namespace App\Entity;

class AbstractEntity
{
    /**
     * @return string
     */
    public function getStateCountry(): string
    {
        return $_ENV['APP_STATE_COUNTRY'];
    }
}
