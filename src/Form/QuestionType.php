<?php

namespace App\Form;

use App\Entity\QualityCheck\Question;
use App\Service\MasterDataService;
use App\Service\QualityCheckService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    private QualityCheckService $qualityCheckService;

    public function __construct(QualityCheckService $qualityCheckService)
    {
        $this->qualityCheckService = $qualityCheckService;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $flagDefinitions = $this->qualityCheckService->getFlagDefinitions();
        $masterDataQuestions = [];

        foreach (MasterDataService::getConfig() as $category) {
            if (! isset($masterDataQuestions[$category['label']])) {
                $masterDataQuestions[$category['label']] = [];
            }
            foreach ($category['items'] as $key => $question) {
                $label = \strlen($question['label']) > 60
                    ? \substr($question['label'], 0, 60) . '...'
                    : $question['label'];
                $masterDataQuestions[$category['label']][$label] = $category['name'] . ':' . $key;
            }
        }

        $builder
            ->add('question')
            ->add('help');

        // Dynamische Flag-Felder für die JSON flags Property - EINFACHE CHECKBOXEN
        foreach ($flagDefinitions as $flag => $definition) {
            $builder->add($flag, CheckboxType::class, [
                'label' => $definition['description'] ?? \ucfirst($flag),
                'required' => false,
                'property_path' => 'flags[' . $flag . ']',
                'label_attr' => [
                    'class' => 'flag-' . $flag
                ]
            ]);
        }
        
        $builder->add('miniCheck', CheckboxType::class, [
                'label' => 'Mini-Check',
                'required' => false,
                'label_attr' => [
                    'class' => 'mini-check'
                ]
            ])
            ->add('miniCheckInfo', TextareaType::class, ['label' => 'Mini-Check Zusatzinfo', 'required' => false])
            ->add('type', ChoiceType::class, [
                'expanded' => true,
                'label_attr' => [
                    'class' => 'radio-inline'
                ],
                'label' => 'Add formula',
                'choices' => [
                    'Yes' => 'needed',
                    'No' => 'not_needed'
                ],
                'disabled' => $options['questionnaireIsActivated'],
            ])
            ->add('masterDataQuestion', ChoiceType::class, [
                'required' => false,
                'placeholder' => '-- Bitte wählen --',
                'choices' => $masterDataQuestions,
                'help' => 'Hier können Sie eine Frage aus den Stammdaten auswählen, die Antwort wird dann im Qualitäts-Check angezeigt und dient als Unterstützung.',
                'disabled' => $options['questionnaireIsActivated'],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $form = $event->getForm();
            $form->add('formula', FormulaType::class, [
                'attr' => ['style' => 'display: none'],
                'validation_groups' => static function (FormInterface $form): array {
                    if ($form->getParent()->get('type')->getData() === 'not_needed') {
                        return [];
                    }
                    return ['Default'];
                }
            ]);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $form = $event->getForm();
            /** @var Question $data */
            $data = $form->getData();
            if ($data->getType() === 'needed' && $data->getFormula() && $data->getFormula()->getQuestion() === null) {
                $data->getFormula()->setQuestion($data);
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'questionnaireIsActivated' => false,
        ]);
    }
}
