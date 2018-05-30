<?php

namespace App\Repository;

use Pagerfanta\Pagerfanta;
use Ramsey\Uuid\Uuid;

/**
 * Class AbstractApiRepository
 * @package App\Repository
 */
class AbstractApiRepository extends AbstractRepository
{
    /**
     * @param Uuid $userUUID
     * @param $id
     * @return null|object
     */
    public function findForUser(Uuid $userUUID, $id)
    {
        if (!$id) {
            return null;
        }

        return self::findOneByForUser($userUUID, ['id' => $id]);
    }

    /**
     * @param Uuid $userUUID
     * @param $id
     * @return null|object
     */
    public function findByExternalId(Uuid $userUUID, $id)
    {
        if (!$id) {
            return null;
        }

        return self::findOneByForUser($userUUID, ['externalId' => $id]);
    }

    /**
     * @param Uuid $userUUID
     * @return array
     */
    public function findAllForUser(Uuid $userUUID)
    {
        return self::findByForUser($userUUID, []);
    }

    /**
     * @param Uuid $userUUID
     * @param $id
     * @return array
     */
    public function findAllByExternalId(Uuid $userUUID, $id)
    {
        if (!$id) {
            return [];
        }

        return self::findByForUser($userUUID, ['externalId' => $id]);
    }

    /**
     * @param Uuid $userUUID
     * @param array $criteria
     * @param array|null $orderBy
     * @return null|object
     */
    public function findOneByForUser(Uuid $userUUID, array $criteria, array $orderBy = null)
    {
        if (!isset($criteria['userId'])) {
            $criteria['userId'] = $userUUID;
        }

        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * @param Uuid $userUUID
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return array
     */
    public function findByForUser(Uuid $userUUID, array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if (!isset($criteria['userId'])) {
            $criteria['userId'] = $userUUID;
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param Uuid $userUUID
     * @param array $criteria
     * @param array $sorting
     * @param int $page
     * @param int $limit
     * @return Pagerfanta
     */
    public function findPaginatedForUser(Uuid $userUUID, array $criteria = [], array $sorting = [], int $page = 1, $limit = AbstractRepository::DEFAULT_PAGINATOR_LIMIT)
    {
        if (!isset($criteria['userId'])) {
            $criteria['userId'] = $userUUID;
        }

        return $this->createPaginator($criteria, $sorting, $page, $limit);
    }
}
