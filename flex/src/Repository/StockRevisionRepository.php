<?php

namespace App\Repository;

use App\Entity\ProductVariant;
use App\Entity\StockRevision;
use App\Entity\WarehouseZone;

/**
 * Class StockRevisionRepository
 * @package App\Repository
 */
class StockRevisionRepository extends AbstractApiRepository
{
    /**
     * @param ProductVariant $productVariant
     * @param WarehouseZone $warehouseZone
     * @return mixed
     */
    public function getFirstPlannedVariantRevision(ProductVariant $productVariant, WarehouseZone $warehouseZone)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.variant = :variant')
            ->andWhere('o.warehouseZone = :warehouseZone')
            ->andWhere('o.state = :state')
            ->setParameter('warehouseZone', $warehouseZone)
            ->setParameter('variant', $productVariant)
            ->setParameter('state', StockRevision::STATE_PLANNED)
            ->orderBy('o.plannedOnDate', 'ASC')
        ;

        /** @var array $result */
        $result = $qb->getQuery()->getResult();
        if ($result && count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param ProductVariant $productVariant
     * @param WarehouseZone $warehouseZone
     * @return mixed
     */
    public function getLastFinishedVariantRevision(ProductVariant $productVariant, WarehouseZone $warehouseZone)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.variant = :variant')
            ->andWhere('o.warehouseZone = :warehouseZone')
            ->andWhere('o.state = :state')
            ->setParameter('warehouseZone', $warehouseZone)
            ->setParameter('variant', $productVariant)
            ->setParameter('state', StockRevision::STATE_FINISHED)
            ->orderBy('o.recalculatedAt', 'DESC')
        ;

        /** @var array $result */
        $result = $qb->getQuery()->getResult();
        if ($result && count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param ProductVariant $productVariant
     * @param WarehouseZone|null $warehouseZone
     * @param array $states
     * @return mixed
     */
    public function getVariantZoneRevisions(ProductVariant $productVariant, WarehouseZone $warehouseZone = null, array $states = [])
    {
        if (!is_array($states)) {
            $states = [$states];
        }

        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.variant = :variant')
            ->setParameter('variant', $productVariant)
            ->orderBy('o.plannedOnDate', 'ASC')
        ;

        if ($warehouseZone) {
            $qb
                ->andWhere('o.warehouseZone = :warehouseZone')
                ->setParameter('warehouseZone', $warehouseZone)
            ;
        }
        if ($states) {
            $qb
                ->andWhere('o.state IN (:states)')
                ->setParameter('states', $states)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param WarehouseZone $warehouseZone
     * @param string $state
     * @return mixed
     */
    public function getZoneRevisionsByState(WarehouseZone $warehouseZone, string $state)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.warehouseZone = :warehouseZone')
            ->andWhere('o.state = :state')
            ->setParameter('warehouseZone', $warehouseZone)
            ->setParameter('state', $state)
        ;

        return $qb->getQuery()->getResult();
    }
}
