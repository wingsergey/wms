<?php

namespace App\Entity;

use App\Traits\ModelValidation;

/**
 * Class AbstractEntity
 * @package App\Entity
 */
class AbstractEntity
{
    use ModelValidation;

    const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @return \Ramsey\Uuid\Uuid
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        if (method_exists($this, 'getTitle') && $this->getTitle()) {
            return (string) $this->getTitle();
        } elseif (method_exists($this, 'getName') && $this->getName()) {
            return (string) $this->getName();
        } elseif (method_exists($this, 'getId') && $this->getId()) {
            return (string) $this->getId();
        }

        return 'n/a';
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return static::class;
    }
}
