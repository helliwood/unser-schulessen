<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 14:37
 */

namespace App\Entity\Survey;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Survey Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="survey_surveyquestion")
 * @ORM\Entity(repositoryClass="App\Repository\Survey\SurveyQuestionRepository")
 */
class SurveyQuestion implements \JsonSerializable
{
    public const TYPE_HAPPY_UNHAPPY = "happy_unhappy";
    public const TYPE_SINGLE = "single";
    public const TYPE_MULTI = "multi";

    public const TYPE_LABELS = [
        self::TYPE_HAPPY_UNHAPPY => 'zufrieden/unzufrieden',
        self::TYPE_SINGLE => 'Einfachauswahl',
        self::TYPE_MULTI => 'Mehrfachauswahl',
    ];
    /**
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var Survey
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\Survey", inversedBy="questions", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $survey;

    /**
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $question;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="50")
     * @ORM\Column(type="string", length=50, nullable=false, options={"default":"happy_unhappy"})
     */
    private $type;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var ArrayCollection|SurveyQuestionChoice[]
     * @ORM\OneToMany(targetEntity="App\Entity\Survey\SurveyQuestionChoice", mappedBy="question", cascade={"persist"}, orphanRemoval=true)
     */
    private $choices;

    /**
     * @var ArrayCollection|SurveyQuestionAnswer[]
     * @ORM\OneToMany(targetEntity="App\Entity\Survey\SurveyQuestionAnswer", mappedBy="question")
     */
    private $answers;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`answered`", options={"default" : 0})
     */
    private $answered = 0;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`not_answered`", options={"default" : 0})
     */
    private $not_answered = 0;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true, name="sustainable", options={"default":"0"})
     */
    private $sustainable = false;

    /**
     * SurveyQuestion constructor.
     */
    public function __construct()
    {
        $this->choices = new ArrayCollection();
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
     * @return SurveyQuestion
     */
    public function setId(?int $id): SurveyQuestion
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getSurvey(): Survey
    {
        return $this->survey;
    }

    /**
     * @param Survey $survey
     * @return SurveyQuestion
     */
    public function setSurvey(Survey $survey): SurveyQuestion
    {
        $this->survey = $survey;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getQuestion(): ?string
    {
        return $this->question;
    }

    /**
     * @param string $question
     * @return SurveyQuestion
     */
    public function setQuestion(string $question): SurveyQuestion
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return SurveyQuestion
     */
    public function setType(string $type): SurveyQuestion
    {
        $this->type = $type;
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
     * @return SurveyQuestion
     */
    public function setOrder(int $order): SurveyQuestion
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return SurveyQuestionChoice[]|ArrayCollection
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @param SurveyQuestionChoice $surveyQuestionChoice
     * @return SurveyQuestion
     */
    public function addChoice(SurveyQuestionChoice $surveyQuestionChoice): SurveyQuestion
    {
        $surveyQuestionChoice->setQuestion($this);
        $surveyQuestionChoice->setOrder($this->choices->count() + 1);
        $this->choices->add($surveyQuestionChoice);
        $this->reorderChoices();
        return $this;
    }

    /**
     * @param SurveyQuestionChoice $surveyQuestionChoice
     * @return SurveyQuestion
     */
    public function removeChoice(SurveyQuestionChoice $surveyQuestionChoice): SurveyQuestion
    {
        if ($this->choices->contains($surveyQuestionChoice)) {
            $this->choices->removeElement($surveyQuestionChoice);
        }
        $this->reorderChoices();
        return $this;
    }

    /**
     * @return SurveyQuestion
     */
    public function reorderChoices(): SurveyQuestion
    {
        $order = 1;
        foreach ($this->choices as $choice) {
            $choice->setOrder($order);
            $order++;
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function getChoices4Form(): array
    {
        $choices = [];
        foreach ($this->choices as $choice) {
            $choices[$choice->getChoice()] = $choice->getId();
        }

        return $choices;
    }

    /**
     * @param bool|null $answerValue
     * @return SurveyQuestionAnswer[]|ArrayCollection
     */
    public function getAnswers(?bool $answerValue = null)
    {
        if (! \is_null($answerValue)) {
            return $this->answers->filter(static function (SurveyQuestionAnswer $answer) use ($answerValue) {
                return $answer->getAnswer() === $answerValue;
            });
        }
        return $this->answers;
    }

    /**
     * @param SurveyQuestionAnswer[]|ArrayCollection $answers
     * @return SurveyQuestion
     */
    public function setAnswers($answers): SurveyQuestion
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getAnswerLabels(): array
    {
        if ($this->getType() === self::TYPE_HAPPY_UNHAPPY) {
            return ['zufrieden', 'nicht zufrieden'];
        } elseif ($this->getType() === self::TYPE_SINGLE ||
            $this->getType() === self::TYPE_MULTI) {
            $labels = [];
            foreach ($this->getChoices() as $choice) {
                $labels[] = $choice->getChoice();
            }
            return $labels;
        }
        return [];
    }

    /**
     * @return string[]
     */
    public function getAnswerValues(): array
    {
        if ($this->getType() === self::TYPE_HAPPY_UNHAPPY) {
            return [$this->getAnswers(true)->count(), $this->getAnswers(false)->count()];
        } elseif ($this->getType() === self::TYPE_SINGLE ||
            $this->getType() === self::TYPE_MULTI) {
            $values = [];
            foreach ($this->getChoices() as $choice) {
                $values[] = $choice->getAnswers()->count();
            }
            return $values;
        }
        return [];
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
        return $this->getSurvey()->getQuestions()->count() === $this->getOrder();
    }

    /**
     * @return int|null
     */
    public function getAnswered(): ?int
    {
        return $this->answered;
    }

    /**
     * @param int|null $answered
     */
    public function setAnswered(?int $answered): void
    {
        $this->answered = $answered;
    }

    /**
     * @return int|null
     */
    public function getNotAnswered(): ?int
    {
        return $this->not_answered;
    }

    /**
     * @param int|null $not_answered
     */
    public function setNotAnswered(?int $not_answered): SurveyQuestion
    {
        $this->not_answered = $not_answered;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSustainable(): bool
    {
        return $this->sustainable;
    }

    /**
     * @param bool $sustainable
     */
    public function setSustainable(bool $sustainable): void
    {
        $this->sustainable = $sustainable;
    }

    public function __clone()
    {
        $this->setId(null)
            ->setAnswers(new ArrayCollection())
            ->setNotAnswered(0)
            ->setAnswered(0);

        $choices = $this->getChoices();
        $this->choices = new ArrayCollection();
        foreach ($choices as $choice) {
            $choiceClone = clone $choice;
            $this->addChoice($choiceClone);
        }
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'question' => $this->getQuestion(),
            'sustainable' => $this->isSustainable(),
            'type' => $this->getType(),
            'typeLabel' => self::TYPE_LABELS[$this->getType()],
            'first' => $this->isFirst(),
            'last' => $this->isLast()
        ];
    }
}
