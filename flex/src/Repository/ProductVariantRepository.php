<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\WarehouseZone;

/**
 * Class ProductVariantRepository
 * @package App\Repository
 */
class ProductVariantRepository extends AbstractApiRepository
{
    /**
     * @param $code
     * @param $productCode
     * @return mixed
     */
    public function findOneByCodeAndProductCode($code, $productCode)
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.product', 'product')
            ->andWhere('product.code = :productCode')
            ->andWhere('o.code = :code')
            ->setParameter('productCode', $productCode)
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $code
     * @param $productCode
     * @return array
     */
    public function findByCodeAndProductCode($code, $productCode)
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.product', 'product')
            ->andWhere('product.code = :productCode')
            ->andWhere('o.code = :code')
            ->setParameter('productCode', $productCode)
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $id
     * @param $productId
     * @return mixed
     */
    public function findOneByIdAndProductId($id, $productId)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.product = :productId')
            ->andWhere('o.id = :id')
            ->setParameter('productId', $productId)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param WarehouseZone $zone
     * @return mixed
     */
    public function getZoneVariants(WarehouseZone $zone)
    {
        return $this->createQueryBuilder('o')
            ->distinct()
            ->innerJoin('o.stockHistories', 'sh', 'WITH')
            ->andWhere('sh.warehouseZone = :zone')
            ->setParameter('zone', $zone)
            ->getQuery()
            ->getResult();
    }
}
