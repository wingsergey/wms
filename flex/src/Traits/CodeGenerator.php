<?php

namespace App\Traits;

/**
 * Trait CodeGenerator
 * @package App\Traits
 */
trait CodeGenerator
{
    /**
     * @return int
     */
    public function generateCode()
    {
        $parts = explode(' ', microtime(false));
        $code  = (int) ($parts[0] * $parts[1]);

        return $code;
    }
}
