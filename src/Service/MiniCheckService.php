<?php

namespace App\Service;

use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\MiniCheckResult;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Range;

class MiniCheckService
{
    protected EntityManagerInterface $entityManager;

    protected FormFactoryInterface $formFactory;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    public function createMiniCheckResult(): MiniCheckResult
    {
        $result = new MiniCheckResult();
        $result->setQuestionnaire($this->entityManager->getRepository(Questionnaire::class)->findOneBy(['state' => Questionnaire::STATE_ACTIVE]));
        return $result;
    }


    /**
     * @param MiniCheckResult $result
     * @param array|string[]|null $formData
     * @param bool $disable_csrf
     * @return FormInterface
     * @throws NonUniqueResultException
     */
    public function getForm(MiniCheckResult $result, ?array $formData = null, bool $disable_csrf = false): FormInterface
    {
        $formBuilder = $this->formFactory->createNamedBuilder("MiniCheck", FormType::class, null, [
            'csrf_protection' => ! $disable_csrf,
            'master_data_question' => []
        ]);

        /** @var Answer[] $answers */
        $answers = $result->getAnswers()->toArray();

        /**
         * @param Collection|Question[] $questions
         * @param FormBuilderInterface $formBuilder
         * @throws NonUniqueResultException
         */
        $addQuestionsToForm = static function (array $questions, FormBuilderInterface $formBuilder) use ($answers, $formData): void {
            foreach ($questions as $question) {
                $data = isset($answers[$question->getId()])
                    ? $answers[$question->getId()]->getAnswer()
                    : null;

                if (isset($formData[$question->getId()])) {
                    $data = $formData[$question->getId()];
                }

                $masterDataQuestion = ! \is_null($question->getHelp()) ? '<div class="mb-2"><span class="text-dark font-weight-bold mr-1">Hinweis:</span>' . $question->getHelp() . '</div>' : '';
                if (! \is_null($question->getMiniCheckInfo())) {
                    $masterDataQuestion = '<span class="text-muted mb-2">' . $question->getMiniCheckInfo() . '</span> ';
                }

                switch ($question->getType()) {
                    case Question::TYPE_NEEDED:
                        $formBuilder->add($question->getId(), IntegerType::class, [
                            'required' => false,
                            'label' => $question->getQuestion(),
                            'attr' => [
                                'style' => 'width: 100px;',
                                'data-question' => $question
                            ],
                            'label_attr' => ['class' => $question->isFlagEqual('sustainable', true) ? 'sustainable' : ''],
                            'help' => $masterDataQuestion,
                            'data' => \is_numeric($data) ? $data : null,
                            'constraints' => [
                                new Range(['min' => 0, 'max' => 1000])
                            ]
                        ]);
                        break;
                    case Question::TYPE_NOT_NEEDED:
                        $formBuilder->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'label_attr' => ['class' => $question->isFlagEqual('sustainable', true) ? 'sustainable' : ''],
                            'expanded' => true,
                            'data' => $data,
                            'help' => $masterDataQuestion,
                            'attr' => [
                                'data-question' => $question
                            ],
                            'choices' => [
                                Answer::ANSWER_LABELS[Answer::ANSWER_TRUE] => Answer::ANSWER_TRUE,
                                Answer::ANSWER_LABELS[Answer::ANSWER_PARTIAL] => Answer::ANSWER_PARTIAL,
                                Answer::ANSWER_LABELS[Answer::ANSWER_FALSE] => Answer::ANSWER_FALSE,
                                Answer::ANSWER_LABELS[Answer::ANSWER_NOT_ANSWERED] => Answer::ANSWER_NOT_ANSWERED
                            ]
                        ]);
                        break;
                    default:
                        throw new \Exception('Fragetyp nicht bekannt!');
                }
            }
        };
        $addQuestionsToForm($this->entityManager->getRepository(Question::class)->find4MiniCheckByQuestionnaire($result->getQuestionnaire()), $formBuilder, $this);

        return $formBuilder->getForm();
    }

    public function isMiniCheckAvailable(): bool
    {
        $questionnaire = $this->entityManager->getRepository(Questionnaire::class)->findOneBy(['state' => Questionnaire::STATE_ACTIVE]);
        if ($questionnaire === null) {
            return false;
        }
        $questions = $this->entityManager->getRepository(Question::class)->find4MiniCheckByQuestionnaire($questionnaire);
        return \count($questions) > 0;
    }
}
