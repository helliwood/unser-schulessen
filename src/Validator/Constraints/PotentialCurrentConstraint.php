<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PotentialCurrentConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Es können nicht mehr Schüler in den einzelnen Jahrgangsstufen sein, als es überhaupt insgesamt an der Schule gibt!';
}
