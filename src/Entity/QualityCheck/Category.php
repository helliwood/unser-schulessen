<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 28.03.19
 * Time: 14:37
 */

namespace App\Entity\QualityCheck;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Category Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(uniqueConstraints={
 *   @UniqueConstraint(columns={"questionnaire_id", "parent_id", "name"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class Category implements \JsonSerializable
{
    /**
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     *
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private $name;

    /**
     * @var string|null
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $note;

    /**
     * @var string|null
     * @Assert\Length(max="512")
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $miniCheckInfo;

    /**
     *
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Category")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $previous;

    /**
     * @var Category|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Category", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var Category[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\Category", mappedBy="parent", orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"order":"ASC"})
     */
    private $children;

    /**
     * @var Questionnaire
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Questionnaire", inversedBy="categories")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $questionnaire;

    /**
     * @var Question[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\Question", mappedBy="category", orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"order":"ASC"})
     */
    private $questions;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    /**
     * Wird vom Form aufgerufen
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $categories = ! \is_null($this->getParent()) ? $this->getParent()->getChildren() : $this->getQuestionnaire()->getCategories();
        foreach ($categories as $category) {
            if ($category->getName() === $this->getName() && $category->getId() !== $this->getId()) {
                $context->buildViolation('Name bereits verwendet!')->atPath('name')->addViolation();
            }
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Category
     */
    public function setName(string $name): Category
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     * @return Category
     */
    public function setNote(?string $note): Category
    {
        $this->note = $note;
        return $this;
    }

    public function getMiniCheckInfo(): ?string
    {
        return $this->miniCheckInfo;
    }

    public function setMiniCheckInfo(?string $miniCheckInfo): Category
    {
        $this->miniCheckInfo = $miniCheckInfo;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return Category
     */
    public function setOrder(int $order): Category
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return Questionnaire
     */
    public function getPrevious(): Questionnaire
    {
        return $this->previous;
    }

    /**
     * @param Category $previous
     * @return Category
     */
    public function setPrevious(Category $previous): Category
    {
        $this->previous = $previous;
        return $this;
    }

    /**
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * @param Category|null $parent
     * @return Category
     * @throws \Exception
     */
    public function setParent(?Category $parent): ?Category
    {
        if (! \is_null($parent->getParent())) {
            throw new \Exception('Parent can\'t have a parent');
        }
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Category[]|ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Category[]|ArrayCollection $children
     * @return Category
     */
    public function setChildren(array $children): Category
    {
        $this->children = $children;
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
     * @return Category
     */
    public function setQuestionnaire(Questionnaire $questionnaire): Category
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    /**
     * @return Question[]|ArrayCollection
     */
    public function getQuestions(?array $flags = null, ?bool $withChildren = false)
    {
        $questions = new ArrayCollection();

        // Eigene Fragen berücksichtigen (ggf. nach Flags gefiltert)
        foreach ($this->questions as $question) {
            if ($flags === null || $question->matchesFlags($flags)) {
                $questions->set($question->getId(), $question);
            }
        }
        // Optional: Kinder rekursiv einbeziehen
        if ($withChildren) {
            foreach ($this->getChildren() as $childCategory) {
                foreach ($childCategory->getQuestions($flags, true) as $childQuestion) {
                    $questions->set($childQuestion->getId(), $childQuestion);
                }
            }
        }

        return $questions;
    }

    public function addQuestion(Question $question): self
    {
        $this->questions->add($question);
    }

    /**
     * @param Question[]|ArrayCollection $questions
     * @return Category
     */
    public function setQuestions($questions): Category
    {
        $this->questions = $questions;
        return $this;
    }


    /**
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->getOrder() === 1;
    }

    /**
     * @return bool
     */
    public function isLast(): bool
    {
        return ! \is_null($this->getParent()) ?
            $this->getParent()->getChildren()->count() === $this->getOrder()
            :
            $this->getQuestionnaire()->getCategories()->count() === $this->getOrder();
    }

    /**
     * @param Result $result
     * @param array|null $flags
     * @return int
     */
    public function getNumberOfQuestions(Result $result, ?array $flags = []): int
    {
        $numberOfQuestions = $this->getQuestions()->filter(function (Question $question) use ($flags) {

//            \dd($result->getAnswers()->map(function (Answer $answer) use ($question) {
//                return $answer->getQuestion() === $question && $answer->getAnswer() !== null;
//            }));
            if ($flags) {
                return $question->matchesFlags($flags);
            }
            return true;
        })->count();

        foreach ($this->getChildren() as $subcategory) {
            $numberOfQuestions += $subcategory->getNumberOfQuestions($result, $flags);
        }

        return $numberOfQuestions;
    }

    /**
     * @param Collection|array $answers
     * @param array|null $flags
     * @return bool
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function isAnswered($answers, ?array $flags = []): bool
    {
        return $this->questions->exists(
        /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter */

            function ($_key, $question) use ($answers, $flags) {
                // Frage nach Flags filtern
                /** @var Question $question */
                if ($flags && ! $question->matchesFlags($flags)) {
                    return false;
                }

                // Prüfen, ob es eine Antwort zu dieser Frage gibt
                /** @var Answer $answer */
                foreach ($answers as $answer) {
                    if ($answer->getQuestion() === $question && $answer->getAnswer() !== null) {
                        return true;
                    }
                }
                return false;
            }
        );
    }

    /**
     * @param Result $result
     * @param string|null $value
     * @param array|null $flags
     * @return int
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function countAnswers(Result $result, ?string $value = null, ?array $flags = []): int
    {
        $answered = $this->questions->filter(function (Question $question) use ($result, $value) {
            /** @var Answer|null $answer */
            $answer = $result->getAnswers()->filter(
                fn(Answer $a) => $a->getQuestion() === $question
            )->first();

            // Keine Antwort vorhanden → nicht gezählt
            if (! $answer) {
                return false;
            }

            // Wenn ein bestimmter Wert geprüft werden soll (auch null ist erlaubt)
            if (\func_num_args() > 1) {
                return $answer->calculateAnswer() === $value;
            }

            // Wenn kein spezieller Wert gefordert ist → einfach "existiert"
            return true;
        });

        return $answered->count();
    }

    public function getQuestionsByFlag(string $flag, ?bool $withChildren = true): Collection
    {
        $questions = new ArrayCollection();

        foreach ($this->questions as $question) {
            if ($question->isFlagEqual($flag)) {
                $questions->add($question);
            }
        }
        if ($withChildren) {
            foreach ($this->getChildren() as $child) {
                foreach ($child->getQuestionsByFlag($flag) as $childQuestion) {
                    $questions->add($childQuestion);
                }
            }
        }

        return $questions;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }


    /**
     * Reorders Questions
     */
    public function reorderQuestions(): void
    {
        $order = 1;
        foreach ($this->getQuestions() as $question) {
            $question->setOrder($order);
            $order++;
        }
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'order' => $this->getOrder(),
            'first' => $this->isFirst(),
            'last' => $this->isLast(),
            'questions' => $this->getQuestions(null, true)->count(),
            'sustainableQuestions' => $this->getQuestionsByFlag('sustainable')->count(),
            'categories' => $this->getChildren()->count()
        ];
    }
}
