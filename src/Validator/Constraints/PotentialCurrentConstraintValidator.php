<?php

namespace App\Validator\Constraints;

use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PotentialCurrentConstraintValidator extends ConstraintValidator
{
    /**
     * @param String $value
     * @param Constraint $constraint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof PotentialCurrentConstraint) {
            throw new UnexpectedTypeException($constraint, Integer::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (! \is_int($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'integer');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }
        $data = $this->context->getRoot()->getViewData();

        $summe = $data['class_level_1_4'] + $data['class_level_5_10'] + $data['class_level_11_13'];

        if ($value < $summe) {
            // the argument must be a string or an object implementing __toString()
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
