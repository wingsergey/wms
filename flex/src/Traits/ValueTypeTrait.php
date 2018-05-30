<?php

namespace App\Traits;

use App\Entity\AbstractEntity;
use App\Entity\ProductAttributeValue;

/**
 * Trait ValueTypeTrait
 * @package App\Traits
 */
trait ValueTypeTrait
{
    /**
     * @return mixed
     */
    public function getRawValue()
    {
        return $this->value;
    }

    /**
     * @return bool|false|string
     */
    public function getValue()
    {
        switch ($this->type) {
            case ProductAttributeValue::TYPE_TIMESTAMP:
                return date(AbstractEntity::DEFAULT_DATE_FORMAT, (int) $this->value);
                break;

            case ProductAttributeValue::TYPE_BOOLEAN:
                if ($this->value === 'false') {
                    return false;
                } elseif ($this->value === 'true') {
                    return true;
                }
                return (bool) $this->value;
                break;
        }

        return (string) $this->value;
    }
}
