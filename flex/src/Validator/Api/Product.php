<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Product
 * @package App\Validator\Api
 */
class Product extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function delete(Request $request)
    {
        $constraint = new Assert\Collection([
            'productId' => [
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
    public static function generateVariants(Request $request)
    {
        $constraint = new Assert\Collection([
            'productId' => [
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
    public static function create(Request $request)
    {
        $constraint = new Assert\Collection([
            'name'                   => new Assert\NotBlank(),
            'defaultUnitId'          => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'variantSelectionMethod' => new Assert\Optional(new Assert\Choice([
                \App\Entity\Product::VARIANT_SELECTION_CHOICE,
                \App\Entity\Product::VARIANT_SELECTION_MATCH,
            ])),
            'description'            => new Assert\Optional(),
            'externalId'             => new Assert\Optional(),
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
            'defaultUnitId'          => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'variantSelectionMethod' => new Assert\Optional(new Assert\Choice([
                \App\Entity\Product::VARIANT_SELECTION_CHOICE,
                \App\Entity\Product::VARIANT_SELECTION_MATCH,
            ])),
            'name'                   => new Assert\Optional(new Assert\NotBlank()),
            'description'            => new Assert\Optional(),
            'externalId'             => new Assert\Optional(),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function getBalance(Request $request)
    {
        $constraint = new Assert\Collection([
            'unitId' => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}