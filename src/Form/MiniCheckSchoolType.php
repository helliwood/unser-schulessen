<?php

namespace App\Form;

use App\Entity\School;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MiniCheckSchoolType extends AbstractType
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
            ->add('name', null, ['label' => 'Schulname'])
            ->add('schoolType', ChoiceType::class, [
                'required' => true,
                'constraints' => [new NotBlank()],
                'choices' => [
                    'Grundschule' => 'Grundschule',
                    'weiterführende Schule' => 'weiterführende Schule',
                    'Berufsschule' => 'Berufsschule',
                ],
            ])
            ->add('address', MiniCheckAddressType::class);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => School::class,
            'is_admin_area' => false,
        ]);
    }
}
