<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangeEmailType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($builder->getOption('use_password') === true) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Passwort',
                'empty_data' => null,
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Ihr Passwort',
                    'autocomplete' => 'new-password'
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
        }

        $builder->add('email', RepeatedType::class, [
            'type' => TextType::class,
            'invalid_message' => 'Die neue E-Mail wurde falsch wiederholt.',
            'constraints' => [
                new NotBlank(),
            ],
            'first_options' => [
                'label' => 'Neue E-Mail',
                'attr' => [
                    'placeholder' => 'Ihre neue E-Mail',
                    'autocomplete' => 'new-password'
                ]],
            'second_options' => [
                'label' => 'Neue E-Mail wiederholen',
                'attr' => [
                    'placeholder' => 'Bitte die neue E-Mail wiederholen',
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
            'use_password' => true,
        ]);
    }
}
