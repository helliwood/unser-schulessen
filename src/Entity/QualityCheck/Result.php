<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 28.03.19
 * Time: 14:37
 */

namespace App\Entity\QualityCheck;

use App\Entity\School;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Result Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\QualityCheck\ResultRepository")
 */
class Result implements \JsonSerializable
{

    public const ANSWER_TRUE = "true";
    public const ANSWER_PARTIAL = "partial";
    public const ANSWER_FALSE = "false";
    public const ANSWER_NOT_ANSWERED = null;

    public const ANSWER_LABELS = [
        self::ANSWER_TRUE => "Erfüllt",
        self::ANSWER_PARTIAL => "Teilweise erfüllt",
        self::ANSWER_FALSE => "Nicht erfüllt",
        self::ANSWER_NOT_ANSWERED => "Nicht beantwortet"
    ];

    /**
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected $id;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", inversedBy="results")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    protected $school;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected $createdBy;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastEditedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected $lastEditedBy;

    /**
     *
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $finalised = false;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected $finalisedBy;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $finalisedAt;

    /**
     * @var Questionnaire
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Questionnaire")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected $questionnaire;

    /**
     * @var Answer[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\Answer", mappedBy="result", cascade={"persist"}, orphanRemoval=true, indexBy="question_id")
     */
    private $answers;

    /**
     * Result constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->answers = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Result
     */
    public function setId(?int $id): Result
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return School|null
     */
    public function getSchool(): ?School
    {
        return $this->school;
    }

    /**
     * @param School|null $school
     * @return Result
     */
    public function setSchool(?School $school): Result
    {
        $this->school = $school;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     * @return Result
     */
    public function setCreatedBy(?User $createdBy): Result
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return Result
     */
    public function setCreatedAt(?\DateTime $createdAt): Result
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastEditedAt(): ?\DateTime
    {
        return $this->lastEditedAt;
    }

    /**
     * @param \DateTime|null $lastEditedAt
     * @return Result
     */
    public function setLastEditedAt(?\DateTime $lastEditedAt): Result
    {
        $this->lastEditedAt = $lastEditedAt;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getLastEditedBy(): ?User
    {
        return $this->lastEditedBy;
    }

    /**
     * @param User|null $lastEditedBy
     * @return Result
     */
    public function setLastEditedBy(?User $lastEditedBy): Result
    {
        $this->lastEditedBy = $lastEditedBy;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isFinalised(): ?bool
    {
        return $this->finalised;
    }

    /**
     * @param bool|null $finalised
     * @return Result
     */
    public function setFinalised(?bool $finalised): Result
    {
        $this->finalised = $finalised;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getFinalisedBy(): ?User
    {
        return $this->finalisedBy;
    }

    /**
     * @param User|null $finalisedBy
     * @return Result
     */
    public function setFinalisedBy(?User $finalisedBy): Result
    {
        $this->finalisedBy = $finalisedBy;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getFinalisedAt(): ?\DateTime
    {
        return $this->finalisedAt;
    }

    /**
     * @param \DateTime|null $finalisedAt
     * @return Result
     */
    public function setFinalisedAt(?\DateTime $finalisedAt): Result
    {
        $this->finalisedAt = $finalisedAt;
        return $this;
    }

    /**
     * @return Questionnaire
     */
    public function getQuestionnaire(): Questionnaire
    {
        return $this->questionnaire;
    }

    /**
     * @param Questionnaire $questionnaire
     * @return Result
     */
    public function setQuestionnaire(Questionnaire $questionnaire): Result
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    /**
     * @param array|null $flags
     * @return Answer[]|PersistentCollection
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getAnswers(?array $flags = []): ArrayCollection
    {
        // Wenn keine Flags => Original-Collection zurück
        if (empty($flags)) {
            return new ArrayCollection($this->answers->toArray());
        }

        // Gefilterte neue Collection (nicht die echte ersetzen!)
        $filtered = new ArrayCollection();

        foreach ($this->answers as $answer) {
            if ($answer->getQuestion()->matchesFlags($flags)) {
                $filtered->set($answer->getQuestion()->getId(), $answer);
            }
        }

        return $filtered;
    }
//    public function getAnswers(?array $flags = []): PersistentCollection
//    {
//        if (! empty($flags)) {
//            $filtered = new PersistentCollection();
//            foreach ($this->answers as $answer) { /** TODO: was mit den null answers????? */
//                if ($answer->getQuestion()->matchesFlags($flags) /*&& $answer->getAnswer() !== null*/) {
//                    $filtered->add($answer);
//                }
//            }
//            $this->answers = $filtered;
//            return $filtered;
//        }
//        return $this->answers;
//    }

    /**
     * @param string|null $value
     * @return int
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function countAnswers(?string $value = null): int
    {
        if (\is_null($value)) {
            return $this->getAnswers()->filter(function (Answer $answer) use ($value) {
                if ($answer->calculateAnswer() !== $value) {
                    return true;
                }
            })->count();
        }

        return $this->getAnswers()->filter(function (Answer $answer) use ($value) {
            if ($answer->calculateAnswer() === $value) {
                return true;
            }
        })->count();
    }

    /**
     * @param Answer[]|ArrayCollection $answers
     * @return Result
     */
    public function setAnswers($answers): Result
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @param array|null $flags
     * @return int|null
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function countCategories(?array $flags = []): int
    {
        $categories = [];
        foreach ($this->getAnswers($flags) as $answer) {
            $category = $answer->getQuestion()->getCategory();
            if (! \in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }
        return \count($categories);
    }

    /**
     * @param array|null $flags
     * @return ArrayCollection|Question[]
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getQuestions(?array $flags = []): ArrayCollection
    {
        $allMatchedQuestions = new ArrayCollection();
        foreach ($this->getAnsweredCategories() as $answeredCategory) {
            $matchedQuestions = $answeredCategory->getQuestions()->filter(function (Question $question) use ($flags) {
                if ($flags === null) {
                    return true;
                }
                return $question->matchesFlags($flags);
            });

            foreach ($matchedQuestions as $q) {
                $allMatchedQuestions->add($q);
            }
        }

        return $allMatchedQuestions;
    }

    /**
     * @param Category $category
     * @param array|null $flags
     * @return array
     */
    public function hasAnsweredQuestion(Category $category, ?array $flags): array
    {
        $categoriesArray = [];

        /** @var Question $question */
        foreach ($category->getQuestions() as $question) {
            if ($question->matchesFlags($flags)) {
                $categoriesArray[] = $category;
            }
        }
        return $categoriesArray;
    }

    /**
     * @param Category $category
     * @param bool $sustainable
     * @param array $flags
     * @return int[]
     */
    public function statsByCategory(Category $category, bool $sustainable = false, array $flags = []): array
    {
        $stats = [];
        foreach ($category->getQuestions() as $question) {
            if (! $this->shouldIncludeQuestion($question, $sustainable, $flags)) {
                continue;
            }
            $questionId = $question->getId();
            if (isset($this->getAnswers()[$questionId])) {
                $answer = $this->getAnswers()[$questionId]->calculateAnswer() ?? "not_answered";
                if (! isset($stats[$answer])) {
                    $stats[$answer] = 0;
                }
                $stats[$answer]++;
            } else {
                if (! isset($stats["not_answered"])) {
                    $stats["not_answered"] = 0;
                }
                $stats["not_answered"]++;
            }
        }
        $order = ['true', 'partial', 'false', 'not_answered'];
        $sorted = [];
        foreach ($order as $item) {
            if (isset($stats[$item]) && $stats[$item] > 0) {
                $sorted[$item] = $stats[$item];
            }
        }
        return $sorted;
    }


    /**
     * Prüft ob eine Frage in die Statistik aufgenommen werden soll
     * @param Question $question
     * @param bool $sustainable
     * @param array $flags
     * @return bool
     */
    private function shouldIncludeQuestion(Question $question, bool $sustainable, array $flags): bool
    {
        // Prüfe sustainable Flag
        if ($sustainable && ! $question->isFlagEqual('sustainable', true)) {
            return false;
        }

        // Prüfe zusätzliche Flags
        foreach ($flags as $flag => $value) {
            if (! $question->isFlagEqual($flag, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Category $category
     * @return bool
     */
    public function hasAnswersInCategory(Category $category): bool
    {
        foreach ($this->getAnswers() as $answer) {
            if ($answer->getQuestion()->getCategory() === $category) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $answerType
     * @param Category $category
     * @param array|null $flags
     * @return array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getAnswersByResultValue(string $answerType, Category $category, ?array $flags = [])
    {
        return \array_filter($this->getAnswers()->getValues(), static function ($element) use ($answerType, $category, $flags) {
            if ((string)$element->calculateAnswer() === $answerType
                && $element->getQuestion()->getCategory() === $category
                && $element->getQuestion()->matchesFlags($flags)) {
                return true;
            }
        });
    }

    /**
     * @param array $flags
     * @return float
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getResultRatio(array $flags = []): float
    {
        if ($this->getQuestionnaire()->countQuestions($this, $flags) === 0) {
            return 0;
        }
        return $this->countAnswers(self::ANSWER_TRUE) / $this->getQuestionnaire()->countQuestions($this, $flags);
    }

    /**
     * @param array|null $flags
     * @return int
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function countAnsweredMainCategories(?array $flags = []): int
    {
        $mainCategories = [];
        foreach ($this->getAnsweredCategories($flags) as $category) {
            if (\is_null($category->getParent())) {
                // Hauptkategorie
                $mainCategories[$category->getId()] = $category;
            } else {
                // Unterkategorie
                $mainCategories[$category->getParent()->getId()] = $category->getParent();
            }
        }
        return \count($mainCategories);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param array $flags
     * @return array|null
     */
    public function getStatistics(array $flags = []): ?array
    {
        if (! \is_null($this)) {
            $stats = [
                Answer::ANSWER_TRUE => 0,
                Answer::ANSWER_PARTIAL => 0,
                Answer::ANSWER_FALSE => 0,
                'not_answered' => 0
            ];

            foreach ($this->getAnswers($flags) as $answer) {
                if ($this->shouldIncludeAnswer($answer, $flags)) {
                    if (isset($stats[$answer->calculateAnswer()])) {
                        $stats[$answer->calculateAnswer()]++;
                    } else {
                        $stats['not_answered']++;
                    }
                }
            }
            return $stats;
        }
        return null;
    }

    /**
     * Prüft ob eine Antwort in die Zählung aufgenommen werden soll
     * @param Answer $answer
     * @param array $flags
     * @return bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    private function shouldIncludeAnswer(Answer $answer, array $flags): bool
    {
        return $answer->getQuestion()->matchesFlags($flags);
    }

    /**
     * @param array|null $flags
     * @param bool|null $parentsOnly
     * @return array|Category[]
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getAnsweredCategories(?array $flags = [], ?bool $parentsOnly = false): array
    {
        $categoriesArray = [];
        /** @var Category $category */
        foreach ($this->questionnaire->getCategories() as $category) {
            if ($category->isAnswered($this->answers->toArray(), $flags)) {
                $categoriesArray[] = $category;
            }
            if ($parentsOnly === false) {
                foreach ($category->getChildren() as $child) {
                    if ($child->isAnswered($this->answers->toArray(), $flags)) {
                        $categoriesArray[] = $child;
                    }
                }
            }
        }
//dd($categoriesArray);
        return \array_unique($categoriesArray);
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy()->getDisplayName(),
            'finalised' => $this->isFinalised(),
            'finalisedAt' => $this->getFinalisedAt(),
            'finalisedBy' => $this->getFinalisedBy() ? $this->getFinalisedBy()->getDisplayName() : ''
        ];
    }
}
