<?php

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;

class FoodSurveyCloneType extends AbstractType
{

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Name des Tellerchecks',
            ])
            ->add('template', null, [
                'label' => 'Vorlage des Tellerchecks',
            ]);
    }
}
