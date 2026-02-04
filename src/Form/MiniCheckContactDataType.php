<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MiniCheckContactDataType extends AbstractType
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
            ->add('name', TextType::class, [
                'label' => 'Ihr Name:',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Ihre E-Mail:',
                'constraints' => [
                    new NotBlank(),
                ],
            ])->add('dsgvo', CheckboxType::class, [
                'label' => 'Ich bin damit einverstanden, dass oben stehende Daten entsprechend der <a href="https://www.unser-schulessen.de/datenschutz" target="_blank">Datenschutzerklärung</a> in einem automatisierten Verfahren erhoben, gespeichert, verarbeitet werden und ich ihm Rahmen von Unser Schulessen kontaktiert werden darf.',
                'label_html' => true,
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Bitte akzeptieren Sie die Datenschutzbestimmungen']),
                ]
            ]);
    }
}
