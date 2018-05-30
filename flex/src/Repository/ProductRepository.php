<?php

namespace App\Repository;

use Ramsey\Uuid\Uuid;

/**
 * Class ProductRepository
 * @package App\Repository
 */
class ProductRepository extends AbstractApiRepository
{
    /**
     * @param Uuid $userUUID
     * @return array
     */
    public function findAllForUser(Uuid $userUUID)
    {
        return self::findByForUser($userUUID, [], [
            'name' => 'ASC',
        ]);
    }
}
