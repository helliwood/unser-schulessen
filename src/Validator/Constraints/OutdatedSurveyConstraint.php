<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OutdatedSurveyConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Die letzte Schülerbefragung kann nicht in der Zukunft gewesen sein!';
}
