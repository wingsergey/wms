<?php

namespace App\Entity;

/**
 * Class AbstractApiEntity
 * @package App\Entity
 */
class AbstractApiEntity extends AbstractEntity implements \JsonSerializable, ApiEntityInterface
{
    const DATES_FORMAT     = DATE_ISO8601;
    const DEFAULT_CURRENCY = 'грн';

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() : string
    {
        // This entity implements JsonSerializable (http://php.net/manual/en/class.jsonserializable.php)
        // so this method is used to customize its JSON representation when json_encode()
        // is called, for example in tags|json_encode (app/Resources/views/form/fields.html.twig)

        return $this->__toString();
    }

    /**
     * @param bool $lazy
     * @return array
     */
    public function getApiFields(bool $lazy = false) : array
    {
        if ($lazy) {
            return [$this->getId()->__toString()];
        }

        return [
            'id'         => $this->getId()->__toString(),
            'identifier' => $this->__toString(),
            'class'      => static::class,
        ];
    }

    /**
     * @param int $cost
     * @return float
     */
    public static function roundPrice(int $cost) : float
    {
        return round($cost / 100, 2);
    }

    /**
     * @param int $cost
     * @return string
     */
    public static function formatPrice(int $cost) : string
    {
        return (string) (self::roundPrice($cost) . ' ' . self::DEFAULT_CURRENCY);
    }
}
