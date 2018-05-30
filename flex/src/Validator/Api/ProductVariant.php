<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductVariant
 * @package App\Validator\Api
 */
class ProductVariant extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function delete(Request $request)
    {
        $constraint = new Assert\Collection([
            'productVariantId' => [
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
            'productId'    => [
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ],
            'name'         => new Assert\Optional(),
            'price'        => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Type([
                    'type'    => 'integer',
                    'message' => 'The price {{ value }} is not a valid {{ type }}.',
                ]),
            ]),
            'master'       => [
                new Assert\Optional([
                    new Assert\Type([
                        'type'    => 'bool',
                        'message' => 'The master flag {{ value }} is not a valid {{ type }} value.',
                    ]),
                ]),
            ],
            'code'         => [
                new Assert\Optional(new Assert\NotBlank()),
            ],
            'sku'          => new Assert\Optional(),
            'optionValues' => new Assert\Optional(),
            'externalId'   => new Assert\Optional(),
            'width'        => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The width {{ value }} is not a valid {{ type }}.',
                ])
            ),
            'height'       => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The height {{ value }} is not a valid {{ type }}.',
                ])
            ),
            'depth'        => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The depth {{ value }} is not a valid {{ type }}.',
                ])
            ),
            'weight'       => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The weight {{ value }} is not a valid {{ type }}.',
                ])
            ),
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
            'productId'    => new Assert\Optional([
                new Assert\Uuid(),
                new Assert\NotBlank(),
            ]),
            'name'         => new Assert\Optional(),
            'price'        => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Type([
                    'type'    => 'integer',
                    'message' => 'The price {{ value }} is not a valid {{ type }}.',
                ]),
            ]),
            'code'         => new Assert\Optional(new Assert\NotBlank()),
            'sku'          => new Assert\Optional(),
            'optionValues' => new Assert\Optional(),
            'externalId'   => new Assert\Optional(),
            'width'        => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The width {{ value }} is not a valid {{ type }}.',
                ])
            ),
            'height'       => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The height {{ value }} is not a valid {{ type }}.',
                ])
            ),
            'depth'        => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The depth {{ value }} is not a valid {{ type }}.',
                ])
            ),
            'weight'       => new Assert\Optional(
                new Assert\Type([
                    'type'    => 'numeric',
                    'message' => 'The weight {{ value }} is not a valid {{ type }}.',
                ])
            ),
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
                new Assert\NotBlank(),
                new Assert\Uuid()
            ]),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}