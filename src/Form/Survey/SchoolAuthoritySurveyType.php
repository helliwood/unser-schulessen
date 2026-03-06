<?php

namespace App\Form\Survey;

use App\Entity\Survey\Survey;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchoolAuthoritySurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'empty_data' => '',
                'disabled' => $options['data'] instanceof Survey
                    && $options['data']->getState() !== Survey::STATE_NOT_ACTIVATED,
            ])
            ->add('introduction', TextareaType::class, [
                'label' => 'Einleitungstext',
                'required' => false,
            ])
            ->add('closesAt', DateTimeType::class, [
                'input' => 'datetime',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
        ]);
    }
}
