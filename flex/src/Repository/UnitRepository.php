<?php

namespace App\Repository;

use Ramsey\Uuid\Uuid;

/**
 * Class UnitRepository
 * @package App\Repository
 */
class UnitRepository extends AbstractApiRepository
{
    /**
     * @param Uuid $userUUID
     * @return mixed
     */
    public function getUserStandardUnits(Uuid $userUUID)
    {
        $qb = $this->createQueryBuilder('o');

        $qb
            ->andWhere('o.userId = :user')
            ->andWhere('o.product IS NULL')
            ->setParameter('user', $userUUID);

        return $qb->getQuery()->getResult();
    }
}
