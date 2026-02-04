<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ContactType extends AbstractType
{
    /**
     * @var Security
     */
    protected $security;

    /**
     * PersonType constructor.
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salutation', ChoiceType::class, [
                'placeholder' => '-- Bitte wählen --',
                'choices' => [
                    'Frau' => 'Frau',
                    'Herr' => 'Herr',
                    'Organisation' => 'Organisation'
                ]
            ])
            ->add('academicTitle')
            ->add('firstName')
            ->add('lastName', null, ['label' => 'Name/Organisation'])
            ->add('email')
            ->add('telephone')
            ->add('note', TextareaType::class, ['required' => false]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class
        ]);
    }
}
