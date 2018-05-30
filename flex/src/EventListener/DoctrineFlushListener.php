<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValue;
use App\Entity\ProductOption;
use App\Entity\ProductOptionValue;
use App\Entity\ProductVariant;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

/**
 * Class DoctrineFlushListener
 * @package App\EventListener
 */
class DoctrineFlushListener
{
    /** @var EntityManager */
    private $entityManager;

    /** @var UnitOfWork */
    private $unitOfWork;

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->entityManager = $args->getEntityManager();
        $this->unitOfWork                 = $this->entityManager->getUnitOfWork();

        $entities = array_merge(
            $this->unitOfWork->getScheduledEntityInsertions(),
            $this->unitOfWork->getScheduledEntityUpdates()
        );

        foreach ($entities as $entity) {
            if ($entity instanceof ProductVariant) {
                $this->refreshProductVariant($entity);
            }

            if ($entity instanceof Product) {
                $this->refreshProduct($entity);
            }

            if ($entity instanceof ProductAttribute) {
                $this->refreshProductAttribute($entity);
            }

            if ($entity instanceof ProductAttributeValue) {
                $this->refreshProductAttributeValue($entity);
            }

            if ($entity instanceof ProductOption) {
                $this->refreshProductOption($entity);
            }

            if ($entity instanceof ProductOptionValue) {
                $this->refreshProductOptionValue($entity);
            }
        }
    }

    /**
     * @param ProductOption $entity
     */
    private function refreshProductOption(ProductOption $entity)
    {
        $product = $entity->getProduct();
        /** @var ProductVariant $variant */
        foreach ($product->getVariants() as $variant) {
            $this->refreshProductVariant($variant, false);
        }
        $this->refreshProduct($product);
    }

    /**
     * @param ProductOptionValue $entity
     */
    private function refreshProductOptionValue(ProductOptionValue $entity)
    {
        $this->refreshProductOption($entity->getOption());
    }

    /**
     * @param ProductAttribute $entity
     */
    private function refreshProductAttribute(ProductAttribute $entity)
    {
        /** @var ProductAttributeValue $item */
        foreach ($entity->getValues() as $item) {
            $this->refreshProductAttributeValue($item);
        }
    }

    /**
     * @param ProductAttributeValue $entity
     */
    private function refreshProductAttributeValue(ProductAttributeValue $entity)
    {
        $this->refreshProduct($entity->getProduct());
    }

    /**
     * @param ProductVariant $entity
     * @param bool $refreshProduct
     */
    private function refreshProductVariant(ProductVariant $entity, $refreshProduct = true)
    {
        $productVariantClassMetadata = $this->entityManager->getClassMetadata(ProductVariant::class);

        $entity->refreshDocument();
        $this->unitOfWork->recomputeSingleEntityChangeSet($productVariantClassMetadata, $entity);

        if ($refreshProduct) {
            $this->refreshProduct($entity->getProduct());
        }
    }

    /**
     * @param Product $entity
     */
    private function refreshProduct(Product $entity)
    {
        $productClassMetadata = $this->entityManager->getClassMetadata(Product::class);

        $entity->refreshDocument();
        $this->unitOfWork->recomputeSingleEntityChangeSet($productClassMetadata, $entity);
    }
}
