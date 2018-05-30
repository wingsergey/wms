<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductOption
 * @package App\Validator\Api
 */
class ProductOption extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function delete(Request $request)
    {
        $constraint = new Assert\Collection([
            'productOptionId' => [
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
            'productId'  => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'name'       => new Assert\NotBlank(),
            'externalId' => new Assert\Optional(),
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
            'productId'  => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'name'       => new Assert\Optional(new Assert\NotBlank()),
            'externalId' => new Assert\Optional(),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}