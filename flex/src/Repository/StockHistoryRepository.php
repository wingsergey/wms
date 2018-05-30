<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\StockHistory;

/**
 * Class StockHistoryRepository
 * @package App\Repository
 */
class StockHistoryRepository extends AbstractApiRepository
{
    /**
     * @param StockHistory $stockHistory
     * @return array
     */
    public function findChildHistories(StockHistory $stockHistory)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.originStockHistory = :stockHistory')
            ->setParameter('stockHistory', $stockHistory);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getProductStockHistories(Product $product)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->join('o.variant', 'variant')
            ->andWhere('variant.product = :product')
            ->setParameter('product', $product);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ProductVariant $productVariant
     * @return array
     */
    public function getProductVariantStockHistories(ProductVariant $productVariant)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.variant = :variant')
            ->setParameter('variant', $productVariant);

        return $qb->getQuery()->getResult();
    }
}
