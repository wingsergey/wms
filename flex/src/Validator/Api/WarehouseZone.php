<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class WarehouseZone
 * @package App\Validator\Api
 */
class WarehouseZone extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function create(Request $request)
    {
        $constraint = new Assert\Collection([
            'title'       => new Assert\NotBlank(),
            'description' => new Assert\Optional(),
            'code'        => new Assert\Optional(),
            'nfc'         => new Assert\Optional(),
            'barcode'     => new Assert\Optional(),
            'externalId'  => new Assert\Optional(),
            'width'       => new Assert\Optional(),
            'height'      => new Assert\Optional(),
            'depth'       => new Assert\Optional(),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function update(Request $request)
    {
        $constraint = new Assert\Collection([
            'title'       => new Assert\Optional(new Assert\NotBlank()),
            'description' => new Assert\Optional(),
            'code'        => new Assert\Optional(),
            'nfc'         => new Assert\Optional(),
            'barcode'     => new Assert\Optional(),
            'externalId'  => new Assert\Optional(),
            'width'       => new Assert\Optional(),
            'height'      => new Assert\Optional(),
            'depth'       => new Assert\Optional(),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function delete(Request $request)
    {
        $constraint = new Assert\Collection([
            'warehouseZoneId' => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}