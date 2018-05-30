<?php

namespace App\EventListener;

use App\Entity\ProductVariant;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class VariantReserveChangedEvent
 * @package App\Event
 */
class VariantReserveChangedEvent extends Event
{
    const NAME = 'variant.reserve.changed';

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