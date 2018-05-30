<?php

namespace App\Validator\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Stock
 * @package App\Validator\Api
 */
class Stock extends AbstractValidator
{
    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function addProductToStock(Request $request)
    {
        $constraint = new Assert\Collection([
            'fields' => [
                'variantId'          => new Assert\Uuid(),
                'unitId'             => new Assert\Uuid(),
                'changes'            => [
                    new Assert\Type([
                        'type'    => 'numeric',
                        'message' => 'The changes {{ value }} is not a valid {{ type }}.',
                    ]),
                    new Assert\NotEqualTo(0),
                ],
                'price'              => new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\Type([
                        'type'    => 'numeric',
                        'message' => 'The price {{ value }} is not a valid {{ type }}.',
                    ]),
                ]),
                'externalItemId'     => new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max'        => 255,
                        'maxMessage' => 'Your external item ID string cannot be longer than {{ limit }} characters',
                    ]),
                ]),
                'warehouseId'        => new Assert\Optional(new Assert\Uuid()),
                'warehouseZoneId'    => new Assert\Uuid(),
                'originStockHistory' => new Assert\Optional(new Assert\Uuid()),
                'externalCartId'     => new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max'        => 255,
                        'maxMessage' => 'Your external cart ID string cannot be longer than {{ limit }} characters',
                    ]),
                ]),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function reserveProductOnStock(Request $request)
    {
        $constraint = new Assert\Collection([
            'fields' => [
                'variantId'               => new Assert\Uuid(),
                'unitId'                  => new Assert\Uuid(),
                'changes'                 => [
                    new Assert\Type([
                        'type'    => 'numeric',
                        'message' => 'The changes {{ value }} is not a valid {{ type }}.',
                    ]),
                    new Assert\GreaterThan(0),
                ],
                'price'                   => new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\Type([
                        'type'    => 'numeric',
                        'message' => 'The price {{ value }} is not a valid {{ type }}.',
                    ]),
                ]),
                'externalCartId'          => new Assert\Required([
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max'        => 255,
                        'maxMessage' => 'Your external cart ID string cannot be longer than {{ limit }} characters',
                    ]),
                ]),
                'expirationDateTimestamp' => new Assert\Optional([
                    new Assert\NotBlank(),
                    new Assert\Type([
                        'type'    => 'digit',
                        'message' => 'The value {{ value }} is not a valid timestamp.',
                    ]),
                ]),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function addStockRevision(Request $request)
    {
        $constraint = new Assert\Collection([
            'fields' => [
                'variantId'            => [
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ],
                'unitId'               => [
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ],
                'warehouseZoneId'      => [
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ],
                'qty'                  => [
                    new Assert\Type([
                        'type'    => 'numeric',
                        'message' => 'The quantity {{ value }} is not a valid {{ type }}.',
                    ]),
                    new Assert\GreaterThan(0),
                ],
                'originStockHistoryId' => new Assert\Optional([
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ]),
                'stockRevisionId'      => new Assert\Optional([
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ]),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function planStockRevision(Request $request)
    {
        $constraint = new Assert\Collection([
            'fields' => [
                'variantId'              => [
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ],
                'warehouseZoneId'        => [
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ],
                'plannedOnDateTimestamp' => [
                    new Assert\NotBlank(),
                    new Assert\Type([
                        'type'    => 'digit',
                        'message' => 'The value {{ value }} is not a valid timestamp.',
                    ]),
                ],
                'originStockHistoryId'   => new Assert\Optional([
                    new Assert\Uuid(),
                    new Assert\NotBlank(),
                ]),
            ],
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function deleteHistory(Request $request)
    {
        $constraint = new Assert\Collection([
            'stockHistoryId' => [
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
    public static function deleteReserve(Request $request)
    {
        $constraint = new Assert\Collection([
            'stockReservationId' => [
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
    public static function deleteRevision(Request $request)
    {
        $constraint = new Assert\Collection([
            'stockRevisionId' => [
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