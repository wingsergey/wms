<?php

namespace App\Entity;

/**
 * Interface ApiEntityInterface
 * @package App\Model
 */
interface ApiEntityInterface
{
    /**
     * @param bool $lazy
     * @return mixed
     */
    public function getApiFields(bool $lazy = false);
}
