<?php

namespace App\Factory;

use App\Entity\Product;

/**
 * Class ProductFactory
 * @package App\Factory
 */
class ProductFactory
{
    /**
     * @var ProductVariantFactory
     */
    private $variantFactory;

    /**
     * @param ProductVariantFactory $variantFactory
     */
    public function __construct(
        ProductVariantFactory $variantFactory
    ) {
        $this->variantFactory = $variantFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createNew()
    {
        return new Product();
    }

    /**
     * {@inheritdoc}
     */
    public function createWithVariant()
    {
        $variant = $this->variantFactory->createNew();

        $product = $this->createNew();
        $product->addVariant($variant);

        return $product;
    }
}
