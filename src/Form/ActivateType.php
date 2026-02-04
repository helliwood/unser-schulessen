<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-15
 * Time: 10:22
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Valid;

class ActivateType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('person', ProfileType::class, [
                'label' => "Vervollständigen Sie Ihr Profil",
                'constraints' => [new Valid()]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                //'mapped' => false,
                'invalid_message' => 'Das Passwort wurde nicht korrekt wiederholt.',
                'first_options' => [
                    'label' => 'Passwort',
                    'attr' => [
                        'autocomplete' => 'new-password'
                    ],
                    'help' => 'Das Passwort muss aus mindestens 8 Zeichen bestehen und mindestens eine Zahl und ein Buchstaben enthalten. Erlaubte Sonderzeichen: !@#$%._-'
                ],
                'second_options' => [
                    'label' => 'Passwort wdhl.'
                ],
                'constraints' => [new NotBlank(),
                    new Regex([
                        'pattern' => '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%._-]{8,}$/Uism',
                        'message' => 'Das Passwort erfüllt nicht die Mindestanforderungen oder enthält unerlaubte Zeichen.'
                    ])]
            ])
        ->add('dsgvo', CheckboxType::class, [
            'label' => '<small>Ich bin damit einverstanden, dass oben stehende Daten entsprechend der <a href="https://www.unser-schulessen.de/9_Datenschutz.htm" target="_blank">Datenschutzerklärung</a> in einem automatisierten Verfahren erhoben, gespeichert, verarbeitet werden und ich ihm Rahmen von Unser Schulessen kontaktiert werden darf.</small>',
            'label_html' => true,
            'required' => true,
            'mapped' => false,
            'constraints' => [
                new NotBlank(['message' => 'Bitte akzeptieren Sie die Datenschutzbestimmungen']),
            ]
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
