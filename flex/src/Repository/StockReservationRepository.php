<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductVariant;

/**
 * Class StockReservationRepository
 *
 * @package App\Repository
 */
class StockReservationRepository extends AbstractApiRepository
{
    /**
     * @param Product $product
     * @return array
     */
    public function getProductStockReserves(Product $product)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->join('o.variant', 'variant')
            ->andWhere('variant.product = :product')
            ->andWhere('o.expirationDate > :now OR o.expirationDate IS NULL')
            ->setParameter('product', $product)
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * @return mixed
     */
    public function getExpiredStockReserves()
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.expirationDate <= :now')
            ->andWhere('o.expirationDate IS NOT NULL')
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ProductVariant $productVariant
     * @return array
     */
    public function getProductVariantStockReserves(ProductVariant $productVariant)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.variant = :variant')
            ->andWhere('o.expirationDate > :now OR o.expirationDate IS NULL')
            ->setParameter('variant', $productVariant)
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $externalCartId
     * @param ProductVariant|null $productVariant
     * @return array
     */
    public function getExternalCartStockReserves(string $externalCartId, ProductVariant $productVariant = null)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.expirationDate > :now OR o.expirationDate IS NULL')
            ->andWhere('o.externalCartId = :externalCartId')
            ->setParameter('now', new \DateTime())
            ->setParameter('externalCartId', $externalCartId);

        if ($productVariant instanceof ProductVariant) {
            $qb
                ->andWhere('o.variant = :variant')
                ->setParameter('variant', $productVariant);
        }

        return $qb->getQuery()->getResult();
    }
}
