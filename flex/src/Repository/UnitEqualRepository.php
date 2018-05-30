<?php

namespace App\Repository;

use App\Entity\Product;
use Ramsey\Uuid\Uuid;

/**
 * Class UnitEqualRepository
 * @package App\Repository
 */
class UnitEqualRepository extends AbstractApiRepository
{
    /**
     * @param Uuid $userUUID
     * @return array|mixed
     */
    public function findAllForUser(Uuid $userUUID)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->join('o.storageUnit', 'storageUnit')
            ->andWhere('storageUnit.userId = :user')
            ->setParameter('user', $userUUID);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Uuid $userUUID
     * @param $id
     * @return mixed|null|object
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findForUser(Uuid $userUUID, $id)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->join('o.storageUnit', 'storageUnit')
            ->andWhere('storageUnit.userId = :user')
            ->andWhere('o.id = :id')
            ->setParameter('user', $userUUID)
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Uuid $userUUID
     * @return mixed
     */
    public function getUserStandardUnitEquals(Uuid $userUUID)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->join('o.storageUnit', 'storageUnit')
            ->andWhere('storageUnit.userId = :user')
            ->andWhere('storageUnit.product IS NULL')
            ->setParameter('user', $userUUID);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getProductUnitEquals(Product $product)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->join('o.storageUnit', 'storageUnit')
            ->andWhere('storageUnit.product = :product')
            ->setParameter('product', $product);

        return $qb->getQuery()->getResult();
    }
}
