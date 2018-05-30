<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Unit
 * @package App\Validator\Api
 */
class Unit extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function create(Request $request)
    {
        $constraint = new Assert\Collection([
            'productId'   => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'externalId'  => new Assert\Optional(),
            'title'       => new Assert\NotBlank(),
            'shortForm'   => new Assert\Optional(),
            'description' => new Assert\Optional(),
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
            'productId'   => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'externalId'  => new Assert\Optional(),
            'title'       => new Assert\Optional(new Assert\NotBlank()),
            'shortForm'   => new Assert\Optional(new Assert\Optional()),
            'description' => new Assert\Optional(new Assert\Optional()),
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
            'unitId' => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function createUnitEquality(Request $request)
    {
        $constraint = new Assert\Collection([
            'saleUnitId'    => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'storageUnitId' => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'equal'         => [
                new Assert\NotBlank(),
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The equality {{ value }} is not a valid {{ type }}.',
                ]),
                new Assert\GreaterThan(0),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function updateUnitEquality(Request $request)
    {
        $constraint = new Assert\Collection([
            'saleUnitId'    => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'storageUnitId' => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'equal'         => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The equality {{ value }} is not a valid {{ type }}.',
                ]),
                new Assert\GreaterThan(0),
            ]),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function deleteEquality(Request $request)
    {
        $constraint = new Assert\Collection([
            'unitEqualId' => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}