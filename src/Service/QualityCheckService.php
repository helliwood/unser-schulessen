<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-01
 * Time: 12:05
 */

namespace App\Service;

use App\Entity\MasterDataEntry;
use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\QualityCheck\Result;
use App\Entity\User;
use App\Repository\MasterDataEntryRepository;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Range;

class QualityCheckService
{
    /**
     * @var Security
     */
    protected $security;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * @var string
     */
    protected $stateCountry;

    /**
     * @var MasterDataService
     */
    protected $masterDataService;

    /**
     * Flag-Definitionen mit Icon, Beschreibung und Typ
     */
    public const FLAG_DEFINITIONS = [
        'sustainable' => [
            'description' => 'Nachhaltigkeitskriterium',
            'icon' => 'fas fa-leaf',
            'color' => '#006600'
        ],
    ];

    /**
     * MasterDataService constructor.
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param FormFactoryInterface $formFactory
     * @param ParameterBagInterface $params
     * @param MasterDataService $masterDataService
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, ParameterBagInterface $params, MasterDataService $masterDataService)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->masterDataService = $masterDataService;
        $this->params = $params;
        $this->stateCountry = $params->get('app_state_country');
    }

    /**
     * Gibt das Icon für ein Flag zurück
     * @param string $flag
     * @return string|null
     */
    public function getFlagIcon(string $flag): ?string
    {
        return self::FLAG_DEFINITIONS[$flag]['icon'] ?? null;
    }

    /**
     * Gibt die Beschreibung für ein Flag zurück
     * @param string $flag
     * @return string|null
     */
    public function getFlagDescription(string $flag): ?string
    {
        return self::FLAG_DEFINITIONS[$flag]['description'] ?? null;
    }

    /**
     * Gibt den Typ für ein Flag zurück
     * @param string $flag
     * @return string|null
     */
    public function getFlagType(string $flag): ?string
    {
        return self::FLAG_DEFINITIONS[$flag]['type'] ?? null;
    }

       /**
     * Gibt alle Flag-Definitionen zurück
     * @param ParameterBagInterface $params
     * @return array<string, array<string, string>>
     */
    public function getFlagDefinitions(): array
    {
        $flagDefinitions = self::FLAG_DEFINITIONS;

        $stateCountryFlagsClass = \ucfirst($this->stateCountry) . 'Flags';

        $flagDefinitionsClass = "App\\Service\\FlagDefinitions\\" . $stateCountryFlagsClass;
        if (\class_exists($flagDefinitionsClass)) {
            $flagDefinitions = \array_merge($flagDefinitions, $flagDefinitionsClass::getFlagDefinitions());
        }

        return $flagDefinitions;
    }

    /**
     * Gibt alle Flag-Icons zurück (für Kompatibilität)
     * @return array<string, string>
     */
    public function getFlagIcons(): array
    {
        $icons = [];
        $flagDefinitions = $this->getFlagDefinitions();
        foreach ($flagDefinitions as $flag => $definition) {
            $icons[$flag] = $definition['icon'];
        }
        return $icons;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function hasUnfinalisedQualityCheck(): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $rr = $this->entityManager->getRepository(Result::class);
        $result = $rr->findOneBy(['school' => $user->getCurrentSchool(), 'finalised' => false]);
        return ! \is_null($result) ?? false;
    }

    /**
     * Holt Result nach id oder das aktuellste Result
     * @param int|null $id
     * @param array|null $flags
     * @return Result|object|null
     * @throws NonUniqueResultException
     */
    public function getResult(?int $id = null, ?array $flags = [])
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $rr = $this->entityManager->getRepository(Result::class);

        /** @var Result $result */
        $result = ! \is_null($id) ? $rr->createQueryBuilder('r')
            ->where('r.school = :school')
            ->setParameter('school', $user->getCurrentSchool())
            ->andWhere('r.finalised = true')
            ->andWhere('r.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() : $rr->createQueryBuilder('r')
            ->where('r.school = :school')
            ->setParameter('school', $user->getCurrentSchool())
            ->andWhere('r.finalised = true')
            ->setMaxResults(1)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();


        /** Filtert nach flags. Nicht beantwortete Kategorien werden nach dem Filtern ausgeblendet */
        if ($result && $flags) {
            $filteredCategories = new ArrayCollection();

            /** @var Category $category */
            foreach ($result->getQuestionnaire()->getCategories() as $category) {
                foreach ($category->getChildren() as $childCategory) {
                    if ($childCategory->isAnswered($result->getAnswers(), $flags)
                        && ! $filteredCategories->contains($category)) {
                        $filteredCategories->add($category);
                    }
                }

                if ($category->isAnswered($result->getAnswers(), $flags)
                    && ! $filteredCategories->contains($category)) {
                    $filteredCategories->add($category);
                }
            }

            // Jetzt erst die Kategorien ersetzen
            $result->getQuestionnaire()->setCategories($filteredCategories);

            // Antworten ggf. separat filtern (nicht innerhalb der Category-Loop)
            $result->setAnswers($result->getAnswers($flags));
        }
        return $result;
    }

    /**
     * @return array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getResultsByUsersCurrentSchool(): array
    {
        $school = $this->security->getUser()->getCurrentSchool();
        return $this->entityManager
            ->getRepository(Result::class)
            ->findBy(['school' => $school, 'finalised' => true], ['finalisedAt' => 'DESC']);
    }

    /**
     * @return Result|null
     * @throws \Exception
     */
    public function getLastResult(): ?Result
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $rr = $this->entityManager->getRepository(Result::class);
        $result = $rr->findBy(['school' => $user->getCurrentSchool(), 'finalised' => true], ['finalisedAt' => 'DESC']);
        if (\count($result) > 0) {
            return $result[0];
        }
        return null;
    }



//    /**
//     * Prüft ob eine Antwort in die Statistik aufgenommen werden soll
//     * @param Answer $answer
//     * @param bool $sustainable
//     * @param array $flags
//     * @return bool
//     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
//     */
//    private function shouldIncludeAnswer(Answer $answer, ?array $flags = []): bool
//    {
//        $question = $answer->getQuestion();
//
//        // Wenn keine Flags gesetzt sind, alle Antworten einschließen
//        if (empty($flags)) {
//            return true;
//        }
//
//        return $question->matchesFlags($flags);
//    }
//
//    /**
//     * @param QualityCheckService $qualityCheckService
//     * @param int|null $result
//     * @param bool $sustainable
//     * @param array $flags
//     * @return array
//     * @throws NonUniqueResultException
//     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
//     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
//     */
//    public function getResultCount(
//        ?Result $result = null,
//        array $flags = []
//    ): array {
//        $countAnsweredCriteria = 0;
//        $countAnsweredCategories = 0;
//        $countCriteria = 0;
//        $countCategories = 0;
//
//
//        if (! \is_null($result)) {
//            $categories = $result->getQuestionnaire()->getCategories();
//            $countAnsweredCategories = \count($result->getAnsweredCategories());
//            $countCategories = $result->getQuestionnaire()->getCategories()->count();
//
//            foreach ($categories as $category) {
//                $countCriteria += $category->getQuestions()->count();
//
//                foreach ($category->getChildren() as $subcategory) {
//                    $countCriteria += \count($subcategory->getQuestions());
//                }
//            }
//        }
//        \dd($countAnsweredCriteria, $countAnsweredCategories, $countCriteria);
//        return [
//            'countCategories' => $countCategories,
//            'countCriteria' => $countCriteria,
//            'countAnsweredCriteria' => $countAnsweredCriteria,
//            'countAnsweredCategories' => $countAnsweredCategories,
//        ];
//    }

    /**
     * @param int $question_id
     * @param int|null $value
     * @return string|null
     */
    public function calculateAnswer(int $question_id, ?int $value): ?string
    {
        /** @var QuestionRepository $qr */
        $qr = $this->entityManager->getRepository(Question::class);
        $a = new Answer();
        $a->setQuestion($qr->find($question_id));
        $a->setAnswer($value);
        return $a->calculateAnswer();
    }

    /**
     * @param bool $createIfNotExist
     * @return Result|null
     * @throws \Exception
     */
    public function getUnfinalisedResult(bool $createIfNotExist = true): ?Result
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $rr = $this->entityManager->getRepository(Result::class);
        $result = $rr->findOneBy(['school' => $user->getCurrentSchool(), 'finalised' => false]);
        if (! $result && $createIfNotExist) {
            $qr = $this->entityManager->getRepository(Questionnaire::class);
            $questionnaire = $qr->findOneBy(['state' => Questionnaire::STATE_ACTIVE]);
            if (! $questionnaire) {
                throw new \Exception('Kein aktiver Fragebogen gefunden!');
            }
            $result = new Result();
            $result->setSchool($user->getCurrentSchool());
            $result->setCreatedBy($user);
            $result->setQuestionnaire($questionnaire);
            $this->entityManager->persist($result);
            $this->entityManager->flush($result);
        }
        return $result;
    }

    /**
     * @param int $step
     * @return bool
     * @throws \Exception
     */
    public function hasOnlyFormulaQuestions(int $step): bool
    {
        $result = $this->getUnfinalisedResult();
        if (! $result) {
            throw new \Exception('Ein Ergebnis wurde nicht gefunden oder konnte nicht angelegt werden.');
        }

        $category = $result->getQuestionnaire()->getCategories()->getValues()[$step - 1];
        if (! $category) {
            throw new \Exception('Keine Kategorie zu Schritt ' . $step . ' gefunden!');
        }

        foreach ($category->getQuestions() as $question) {
            if ($question->getType() !== Question::TYPE_NEEDED) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $step
     * @param bool $disable_csrf
     * @return FormInterface
     * @throws \Exception
     */
    public function getForm(int $step, bool $disable_csrf = false): FormInterface
    {
        $result = $this->getUnfinalisedResult();
        if (! $result) {
            throw new \Exception('Ein Ergebnis wurde nicht gefunden oder konnte nicht angelegt werden.');
        }

        /** @var Category $category */
        $category = $result->getQuestionnaire()->getCategories()->getValues()[$step - 1];
        if (! $category) {
            throw new \Exception('Keine Kategorie zu Schritt ' . $step . ' gefunden!');
        }

        $formBuilder = $this->formFactory->createNamedBuilder("Questionnaire", FormType::class, null, [
            'csrf_protection' => ! $disable_csrf,
            'last_result' => $this->getLastResult(),
            'master_data_question' => []
        ]);

        /** @var Answer[] $answers */
        $answers = $result->getAnswers()->toArray();

        /**
         * @param Collection|Question[] $questions
         * @param FormBuilderInterface $formBuilder
         * @param                       $self
         * @throws NonUniqueResultException
         */
        $addQuestionsToForm = function (Collection $questions, FormBuilderInterface $formBuilder, $self) use ($answers): void {
            foreach ($questions as $question) {
                $data = isset($answers[$question->getId()])
                    ? $answers[$question->getId()]->getAnswer()
                    : null;

                $masterDataQuestion = ! \is_null($question->getHelp()) ? '<span class="text-dark font-weight-bold mr-1">Hinweis:</span>' . $question->getHelp() : '';
                if (! \is_null($question->getMasterDataQuestion())) {
                    $mdeKeys = \explode(':', $question->getMasterDataQuestion());
                    /** @var MasterDataEntryRepository $mder */
                    $mder = $self->entityManager->getRepository(MasterDataEntry::class);
                    $mde = $mder->findBySchoolAndStepAndKey($self->security->getUser()->getCurrentSchool(), $mdeKeys[0], $mdeKeys[1]);
                    if ($mde) {
                        $masterDataQuestion = '<span class="text-muted">Stammdaten /</span> ' .
                            $this->masterDataService->getConfigByName($mde->getStep())['items'][$mde->getKey()]['label'];
                        $masterDataQuestion .= ': ' . $mde->getValue();
                    }
                }

                switch ($question->getType()) {
                    case Question::TYPE_NEEDED:
                        $formBuilder->add($question->getId(), IntegerType::class, [
                            'required' => false,
                            'label' => $question->getQuestion(),
                            'attr' => [
                                'style' => 'width: 100px;',
                                'data-question-flags' => $this->getQuestionFlagData($question)
                            ],
                            'label_attr' => ['class' => $this->getQuestionFlagClasses($question)],
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
                            'label_attr' => ['class' => $this->getQuestionFlagClasses($question)],
                            'expanded' => true,
                            'data' => $data,
                            'help' => $masterDataQuestion,
                            'row_attr' => [
                                'data-question-flags' => $this->getQuestionFlagData($question),
                                'data-question-id' => $question->getId()
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
        $addQuestionsToForm($category->getQuestions(), $formBuilder, $this);

        if ($category->getChildren()->count() > 0) {
            foreach ($category->getChildren() as $subcategory) {
                $subFormBuilder = $this->formFactory->createNamedBuilder($subcategory->getId(), FormType::class, null, [
                    'label' => $subcategory->getName(),
                    'help' => $subcategory->getNote(),
                    'row_attr' => ['open' => $result->hasAnswersInCategory($subcategory)]
                ]);
                $addQuestionsToForm($subcategory->getQuestions(), $subFormBuilder, $this);
                $formBuilder->add($subFormBuilder);
            }
        }

        $formBuilder->add('stepPointer', HiddenType::class);

        return $formBuilder->getForm();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param int $step
     * @param array $data
     * @param array $clearCategories
     * @throws \Exception
     */
    public function save(int $step, array $data, array $clearCategories): void
    {
        $result = $this->getUnfinalisedResult();
        if (! $result) {
            throw new \Exception('Ein Ergebnis wurde nicht gefunden oder konnte nicht angelegt werden.');
        }

        /** @var Category $category */
        $category = $result->getQuestionnaire()->getCategories()->getValues()[$step - 1];
        if (! $category) {
            throw new \Exception('Keine Kategorie zu Schritt ' . $step . ' gefunden!');
        }

        $ar = $this->entityManager->getRepository(Answer::class);
        foreach ($data as $questionOrCategoryId => $value) {
            if ($questionOrCategoryId === 'stepPointer') {
                continue;
            }

            if (\is_array($value) && isset($category->getChildren()[$questionOrCategoryId])) {
                if (! \in_array($questionOrCategoryId, $clearCategories)) {
                    foreach ($value as $questionId => $val) {
                        if (! isset($category->getChildren()[$questionOrCategoryId]->getQuestions()[$questionId])) {
                            throw new \Exception('Frage ' . $questionOrCategoryId . ' nicht gefunden!');
                        }
                        $question = $category->getChildren()[$questionOrCategoryId]->getQuestions()[$questionId];
                        $answer = $ar->findOneBy(['result' => $result, 'question' => $question]);
                        if (! $answer) {
                            $answer = new Answer();
                            $answer->setResult($result);
                            $answer->setQuestion($question);
                        }
                        $answer->setAnswer($val);
                        $this->entityManager->persist($answer);
                    }
                }
            } else {
                if (! isset($category->getQuestions()[$questionOrCategoryId])) {
                    throw new \Exception('Frage ' . $questionOrCategoryId . ' nicht gefunden!');
                }
                $question = $category->getQuestions()[$questionOrCategoryId];
                $answer = $ar->findOneBy(['result' => $result, 'question' => $question]);
                if (! $answer) {
                    $answer = new Answer();
                    $answer->setResult($result);
                    $answer->setQuestion($question);
                }
                $answer->setAnswer($value);
                $this->entityManager->persist($answer);
            }
        }

        // Alle Antworten von $clearCategories entfernen
        if (\count($clearCategories) > 0) {
            foreach ($category->getChildren() as $subCategory) {
                if (\in_array($subCategory->getId(), $clearCategories)) {
                    foreach ($subCategory->getQuestions() as $question) {
                        $answer = $ar->findOneBy(['result' => $result, 'question' => $question]);
                        if ($answer) {
                            $this->entityManager->remove($answer);
                        }
                    }
                }
            }
        }

        $result->setLastEditedAt(new \DateTime());
        $result->setLastEditedBy($this->security->getUser());
        $this->entityManager->flush();
    }

    /**
     * @param int $step
     * @throws \Exception
     */
    public function skip(int $step): void
    {
        $result = $this->getUnfinalisedResult();
        if (! $result) {
            throw new \Exception('Ein Ergebnis wurde nicht gefunden oder konnte nicht angelegt werden.');
        }
        /** @var Category $category */
        $category = $result->getQuestionnaire()->getCategories()->getValues()[$step - 1];
        if (! $category) {
            throw new \Exception('Keine Kategorie zu Schritt ' . $step . ' gefunden!');
        }

        $ar = $this->entityManager->getRepository(Answer::class);
        foreach ($category->getQuestions() as $question) {
            $answer = $ar->findOneBy(['result' => $result, 'question' => $question]);
            if (! $answer) {
                $answer = new Answer();
                $answer->setResult($result);
                $answer->setQuestion($question);
            }
            $answer->setAnswer(null);
            $this->entityManager->persist($answer);
        }
        // Subcategories
        foreach ($category->getChildren() as $subcategory) {
            foreach ($subcategory->getQuestions() as $question) {
                $answer = $ar->findOneBy(['result' => $result, 'question' => $question]);
                if (! $answer) {
                    $answer = new Answer();
                    $answer->setResult($result);
                    $answer->setQuestion($question);
                }
                $answer->setAnswer(null);
                $this->entityManager->persist($answer);
            }
        }
        $result->setLastEditedAt(new \DateTime());
        $result->setLastEditedBy($this->security->getUser());
        $this->entityManager->flush();
    }

    /**
     * @param int $step
     * @return Category
     * @throws \Exception
     */
    public function getCategory(int $step): Category
    {
        $result = $this->getUnfinalisedResult();
        if (! $result) {
            throw new \Exception('Ein Ergebnis wurde nicht gefunden oder konnte nicht angelegt werden.');
        }

        return $result->getQuestionnaire()->getCategories()->getValues()[$step - 1];
    }

    /**
     * @throws \Exception
     */
    public function finalise(): Result
    {
        $result = $this->getUnfinalisedResult();
        if (! $result) {
            throw new \Exception('Ein Ergebnis wurde nicht gefunden oder konnte nicht angelegt werden.');
        }
        $hasMinimumOneAnswer = false;
        foreach ($result->getAnswers() as $answer) {
            if (! \is_null($answer->getAnswer())) {
                $hasMinimumOneAnswer = true;
                break;
            }
        }
        if ($hasMinimumOneAnswer === false) {
            throw new \Exception('Es muss mindestens eine Frage beantwortet werden.');
        }
        $result->setFinalised(true);
        $result->setFinalisedAt(new \DateTime());
        $result->setFinalisedBy($this->security->getUser());
        $this->entityManager->persist($result);
        $this->entityManager->flush($result);

        return $result;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getStepsTotal(): int
    {
        return $this->getUnfinalisedResult()->getQuestionnaire()->getCategories()->count();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function copyLastResult(): bool
    {
        $result = $this->getLastResult();
//        dd($result);
        if (! $result) {
            throw new \Exception('Der finalisierte Qualitäts-Check konnte nicht gefunden werden!');
        }

        if ($this->hasUnfinalisedQualityCheck()) {
            throw new \Exception('Sie haben noch einen offenen Qualitäts-Check!');
        }

        $newResult = new Result();
        $newResult->setSchool($result->getSchool());
        $newResult->setCreatedBy($result->getCreatedBy());
        $newResult->setQuestionnaire($result->getQuestionnaire());
        $this->entityManager->persist($newResult);

        /** @var Category $category */
        $categories = $result->getQuestionnaire()->getCategories()->getValues();
        if (! $categories) {
            throw new \Exception('Keine Kategorie gefunden!');
        }

        $ar = $this->entityManager->getRepository(Answer::class);
        foreach ($categories as $category) {
            foreach ($category->getQuestions() as $question) {
                $originalAnswer = $ar->findOneBy(['result' => $result, 'question' => $question]);
                $answer = new Answer();
                $answer->setResult($newResult);
                $answer->setQuestion($question);
                if (! \is_null($originalAnswer)) {
                    $answer->setAnswer($originalAnswer->getAnswer());
                }
                $this->entityManager->persist($answer);
            }

            // Subcategories
            foreach ($category->getChildren() as $subcategory) {
                foreach ($subcategory->getQuestions() as $question) {
                    $originalAnswer = $ar->findOneBy(['result' => $result, 'question' => $question]);
                    $answer = new Answer();
                    $answer->setResult($newResult);
                    $answer->setQuestion($question);
                    if (! \is_null($originalAnswer)) {
                        $answer->setAnswer($originalAnswer->getAnswer());
                    }
                    $this->entityManager->persist($answer);
                }
            }
        }

        $newResult->setLastEditedBy(null);
        $newResult->setLastEditedAt(null);
        $newResult->setFinalised(false);
        $newResult->setFinalisedAt(null);
        $result->setFinalisedBy(null);

        $this->entityManager->flush();

        return true;
    }

    /**
     * Generiert CSS-Klassen basierend auf den Flags einer Question
     * @param Question $question
     * @return string
     */
    private function getQuestionFlagClasses(Question $question): string
    {
        $classes = [];
        $flagDefinitions = $this->getFlagDefinitions();

        foreach ($flagDefinitions as $flagName => $definition) {
            if ($this->questionHasFlag($question, $flagName)) {
                $classes[] = 'flag-' . $flagName;
            }
        }

        return \implode(' ', $classes);
    }

    /**
     * Erstellt JSON-Daten für Flag-Icons basierend auf den Flags einer Question
     * @param Question $question
     * @return string JSON-String mit Flag-Definitionen für aktive Flags
     */
    private function getQuestionFlagData(Question $question): string
    {
        $flags = [];
        $flagDefinitions = $this->getFlagDefinitions();

        foreach ($flagDefinitions as $flagName => $definition) {
            if ($this->questionHasFlag($question, $flagName)) {
                $flags[$flagName] = $definition;
            }
        }

        return \json_encode($flags);
    }

    /**
     * Prüft ob eine Question ein bestimmtes Flag hat (nur noch flags Array, keine legacy Properties mehr)
     * @param Question $question
     * @param string $flagName
     * @return bool
     */
    private function questionHasFlag(Question $question, string $flagName): bool
    {
        // Nur noch flags Array prüfen - alle Entity-Attribute sind deprecated
        $flags = $question->getFlags();
        return isset($flags[$flagName]) && $flags[$flagName] === true;
    }
}
