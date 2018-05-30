<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductOption;
use App\Entity\ProductOptionValue;
use App\Entity\ProductVariant;
use App\Factory\ProductVariantFactory;
use Webmozart\Assert\Assert;

/**
 * Class ProductVariantGenerator
 * @package App\Service
 */
final class ProductVariantGenerator
{
    /**
     * @var ProductVariantFactory
     */
    private $productVariantFactory;

    /**
     * @var CartesianSetBuilder
     */
    private $setBuilder;

    /**
     * @var ProductVariantsParityChecker
     */
    private $variantsParityChecker;

    /**
     * @param ProductVariantFactory $productVariantFactory
     * @param ProductVariantsParityChecker $variantsParityChecker
     */
    public function __construct(
        ProductVariantFactory $productVariantFactory,
        ProductVariantsParityChecker $variantsParityChecker
    ) {
        $this->productVariantFactory = $productVariantFactory;
        $this->setBuilder            = new CartesianSetBuilder();
        $this->variantsParityChecker = $variantsParityChecker;
    }

    /**
     * @param Product $product
     */
    public function generate(Product $product)
    {
        Assert::true($product->hasOptions(), 'Cannot generate variants for an object without options.');

        $optionSet = [];
        $optionMap = [];

        /** @var ProductOption $option */
        foreach ($product->getOptions() as $key => $option) {
            /** @var ProductOptionValue $value */
            foreach ($option->getValues() as $value) {
                $optionSet[$key][]          = $value->getId()->__toString();
                $optionMap[$value->getId()->__toString()] = $value;
            }
        }

        $permutations = $this->setBuilder->build($optionSet);

        foreach ($permutations as $permutation) {
            $variant = $this->createVariant($product, $optionMap, $permutation);

            if (!$this->variantsParityChecker->checkParity($variant, $product)) {
                $product->addVariant($variant);
            }
        }
    }

    /**
     * @param Product $product
     * @param array $optionMap
     * @param mixed $permutation
     *
     * @return ProductVariant
     */
    protected function createVariant(Product $product, array $optionMap, $permutation)
    {
        /** @var ProductVariant $variant */
        $variant = $this->productVariantFactory->createForProduct($product);
        $this->addOptionValue($variant, $optionMap, $permutation);

        return $variant;
    }

    /**
     * @param ProductVariant $variant
     * @param array $optionMap
     * @param mixed $permutation
     */
    private function addOptionValue(ProductVariant $variant, array $optionMap, $permutation)
    {
        if (!is_array($permutation)) {
            $variant->addOptionValue($optionMap[$permutation]);

            return;
        }

        foreach ($permutation as $id) {
            $variant->addOptionValue($optionMap[$id]);
        }
    }
}
