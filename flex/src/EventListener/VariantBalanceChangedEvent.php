<?php

namespace App\EventListener;

use App\Entity\ProductVariant;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class VariantBalanceChangedEvent
 * @package App\Event
 */
class VariantBalanceChangedEvent extends Event
{
    const NAME = 'variant.balance.changed';

    /** @var ProductVariant */
    protected $variant;

    /**
     * VariantBalanceChangedEvent constructor.
     * @param ProductVariant $variant
     */
    public function __construct(ProductVariant $variant)
    {
        $this->variant = $variant;
    }

    /**
     * @return ProductVariant
     */
    public function getProductVariant()
    {
        return $this->variant;
    }
}