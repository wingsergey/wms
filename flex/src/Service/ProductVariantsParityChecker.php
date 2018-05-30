<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductOptionValue;
use App\Entity\ProductVariant;

/**
 * Class ProductVariantsParityChecker
 * @package App\Service
 */
final class ProductVariantsParityChecker
{
    /**
     * @param ProductVariant $variant
     * @param Product $product
     * @return bool
     */
    public function checkParity(ProductVariant $variant, Product $product)
    {
        foreach ($product->getVariants() as $existingVariant) {
            // This check is require, because this function has to look for any other different variant with same option values set
            if ($variant === $existingVariant || count($variant->getOptionValues()) !== count($product->getOptions())) {
                continue;
            }

            if ($this->matchOptions($variant, $existingVariant)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ProductVariant $variant
     * @param ProductVariant $existingVariant
     * @return bool
     */
    private function matchOptions(ProductVariant $variant, ProductVariant $existingVariant)
    {
        /** @var ProductOptionValue $option */
        foreach ($variant->getOptionValues() as $option) {
            if (!$existingVariant->hasOptionValue($option)) {
                return false;
            }
        }

        return true;
    }
}
