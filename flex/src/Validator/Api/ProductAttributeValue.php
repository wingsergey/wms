<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductAttributeValue
 * @package App\Validator\Api
 */
class ProductAttributeValue extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function delete(Request $request)
    {
        $constraint = new Assert\Collection([
            'productAttributeValueId' => [
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
            'productId'   => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'attributeId' => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'value'       => new Assert\NotBlank(),
            'type'        => new Assert\Choice(\App\Entity\ProductAttributeValue::getAvailableTypes()),
            'externalId'  => new Assert\Optional(),
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
            'attributeId' => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'value'       => new Assert\Optional(new Assert\NotBlank()),
            'type'        => new Assert\Optional(new Assert\Choice(\App\Entity\ProductAttributeValue::getAvailableTypes())),
            'externalId'  => new Assert\Optional(),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}