<?php

namespace App\Form\Survey;

use App\Entity\Survey\Survey;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyType extends AbstractType
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
            ->add('name', null, [
                'empty_data' => '',
                'disabled' => $options['data'] instanceof Survey &&
                    $options['data']->getState() === Survey::STATE_ACTIVE ?? false,
            ])
            ->add('type', ChoiceType::class, [
                'disabled' => $options['data'] instanceof Survey &&
                    $options['data']->getState() === Survey::STATE_ACTIVE ?? false,
                'choices' => [
                    'Offen' => Survey::TYPE_OPEN,
                    'Voucher' => Survey::TYPE_VOUCHER
                ]
            ])
            ->add('introduction', TextareaType::class, [
                'label' => "Einleitungstext",
                "required" => false,
            ])
        ;

        if ($options['isAdmin']) {
            $builder->add('surveyTemplate', CheckboxType::class, [
                'help' => 'Hier entscheiden Sie ob diese Umfrage für alle Schulen als Vorlage angezeigt werden soll.',
                'label' => 'Allgemeine Umfragen Vorlage'
            ]);
        }
        if ($options['new']) {
            $builder->add('numberOfVoucher', IntegerType::class, [
                'required' => false
            ]);
        }
        $builder->add('closesAt', DateTimeType::class, [
            'input' => 'datetime',
            'widget' => 'single_text',
            'required' => false
        ]);
        if ($options['new']) {
            $builder->add('template', TextType::class, [
                'data' => $options['template'],
                'required' => false,
                'label' => 'Vorlage',
                'help' => 'Optional: Hier können Sie die Id einer vorhandenen Umfrage eingeben.',
                'mapped' => false
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'new' => false,
            'surveyIsActivated' => false,
            'isAdmin' => false,
            'template' => null
        ]);
    }
}
