<?php

namespace App\Form\Survey;

use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Valid;

class SurveyQuestionType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $disabled = (int)$options['surveyState'] !== (int)Survey::STATE_NOT_ACTIVATED;
        $merged = \array_merge(
            ['help' => 'Formulieren Sie die Frage so, dass Sie sich mit "zufrieden" und "nicht zufrieden" beantworten lässt.'],
            $options['isNew'] ? ['data' => 'Seid ihr zufrieden mit '] : [],
            ['disabled' => $disabled]
        );

        if ($options['type'] === SurveyQuestion::TYPE_HAPPY_UNHAPPY) {
            $builder
                ->add(
                    'question',
                    null,
                    $merged
                );
        } elseif ($options['type'] === SurveyQuestion::TYPE_SINGLE) {
            $builder
                ->add('question', null, [
                    'disabled' => $disabled
                ])
                ->add('choices', CollectionType::class, [
                    'entry_type' => SurveyQuestionChoiceType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'by_reference' => false,
                    'disabled' => $disabled,
                    'constraints' => [new Valid(), new Count(['min' => 1, 'max' => 60])],
                ]);
        } elseif ($options['type'] === SurveyQuestion::TYPE_MULTI) {
            $builder
                ->add('question', null, [
                    'disabled' => $options['surveyState']
                ])
                ->add('choices', CollectionType::class, [
                    'entry_type' => SurveyQuestionChoiceType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'by_reference' => false,
                    'disabled' => $disabled,
                    'constraints' => [new Valid(), new Count(['min' => 1, 'max' => 60])],
                ]);
        } else {
            throw new \Exception('Survey question type not found! ' . $options['type']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyQuestion::class,
            'isNew' => true,
            'type' => SurveyQuestion::TYPE_HAPPY_UNHAPPY,
            'surveyState' => false,
        ]);
    }
}
