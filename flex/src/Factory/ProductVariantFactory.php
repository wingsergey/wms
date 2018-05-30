<?php

namespace App\Factory;

use App\Entity\Product;
use App\Entity\ProductVariant;

/**
 * Class ProductVariantFactory
 * @package App\Factory
 */
class ProductVariantFactory
{
    /**
     * @return ProductVariant
     */
    public function createNew()
    {
        return new ProductVariant();
    }

    /**
     * @param Product $product
     * @return ProductVariant
     */
    public function createForProduct(Product $product)
    {
        $variant = $this->createNew();
        $variant->setProduct($product);
        $variant->setUserId($product->getUserId());

        return $variant;
    }
}
