<?php

namespace App\Traits;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ApiValidation
 * @package App\Traits
 */
trait ApiValidation
{
    /**
     * @param Assert\Collection $constraint
     * @param array $input
     * @return ConstraintViolationListInterface
     */
    public static function validateConstraint(Assert\Collection $constraint, array $input) : ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();

        $constraint->allowExtraFields = true;

        $violations = $validator->validate($input, $constraint);

        return $violations;
    }
}
