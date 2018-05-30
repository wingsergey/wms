<?php

namespace App\Validator\Api;

use App\Traits\ApiValidation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AbstractValidator
 * @package App\Validator\Api
 */
class AbstractValidator
{
    use ApiValidation;

    /**
     * @param Request $request
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public static function listExists(Request $request)
    {
        $constraint = new Assert\Collection([
            'list' => new Assert\NotBlank(),
        ]);

        return self::validateConstraint($constraint, $request->request->all());
    }
}