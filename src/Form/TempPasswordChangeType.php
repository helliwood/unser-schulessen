<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TempPasswordChangeType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('newPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Das neue Passwort wurde falsch wiederholt.',
            'constraints' => [
                new NotBlank(),
            ],
            'first_options' => [
                'label' => 'neues Passwort',
                'attr' => [
                    'placeholder' => 'Ihr neues Passwort',
                    'autocomplete' => 'new-password'
                ]],
            'second_options' => [
                'label' => 'neues Passwort wiederholen',
                'attr' => [
                    'placeholder' => 'Bitte das neue Passwort wiederholen',
                    'autocomplete' => 'new-password'
                ]]
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
