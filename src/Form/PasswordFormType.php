<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PasswordFormType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('oldPassword', PasswordType::class, [
            'label' => 'Altes Passwort',
            'empty_data' => null,
            'required' => true,
            'mapped' => false,
            'attr' => [
                'placeholder' => 'Ihr altes Passwort',
                'autocomplete' => 'new-password'
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('newPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'invalid_message' => 'Das neue Passwort wurde falsch wiederholt.',
            'first_options' => [
                'label' => 'Neues Passwort',
                'attr' => [
                    'placeholder' => 'Ihr neues Passwort',
                    'autocomplete' => 'new-password'
                ],
                'help' => 'Das Passwort muss aus mindestens 8 Zeichen bestehen und mindestens eine Zahl und ein Buchstaben enthalten. Erlaubte Sonderzeichen: !@#$%._-'
            ],
            'second_options' => [
                'label' => 'Neues Passwort wiederholen',
                'attr' => [
                    'placeholder' => 'Bitte das neue Passwort wiederholen',
                    'autocomplete' => 'new-password'
                ]
            ],
            'constraints' => [new NotBlank(),
                new Regex([
                    'pattern' => '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%._-]{8,}$/Uism',
                    'message' => 'Das Passwort erfüllt nicht die Mindestanforderungen oder enthält unerlaubte Zeichen.'
                ])]
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
