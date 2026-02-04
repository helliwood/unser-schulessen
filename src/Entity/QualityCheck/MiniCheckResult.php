<?php

namespace App\Entity\QualityCheck;

use App\Entity\School;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class MiniCheckResult
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    protected ?School $school = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected ?DateTime $createdAt = null;

    /**
     * @var Questionnaire
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Questionnaire")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected Questionnaire $questionnaire;

    /**
     * @var MiniCheckAnswer[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\MiniCheckAnswer", mappedBy="result", cascade={"persist"}, orphanRemoval=true, indexBy="question_id")
     */
    private $answers;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): MiniCheckResult
    {
        $this->id = $id;
        return $this;
    }

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function setSchool(?School $school): MiniCheckResult
    {
        $this->school = $school;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): MiniCheckResult
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getQuestionnaire(): Questionnaire
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(Questionnaire $questionnaire): MiniCheckResult
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    /**
     * @return MiniCheckAnswer[]|ArrayCollection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param MiniCheckAnswer[]|ArrayCollection $answers
     * @return MiniCheckResult
     */
    public function setAnswers($answers): MiniCheckResult
    {
        $this->answers = $answers;
        return $this;
    }

    public function addAnswer(MiniCheckAnswer $answer): MiniCheckResult
    {
        $answer->setResult($this);
        $this->answers->add($answer);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getStats(): array
    {
        $stats = ['maxPoints' => 0, 'points' => 0, 'percentage' => 0];
        foreach ($this->answers as $answer) {
            $stats['maxPoints'] += 2;
            $answerType = $answer->calculateAnswer();
            if ($answerType === null) {
                $answerType = 'not_answered';
            }
            if (! isset($stats[$answerType])) {
                $stats[$answerType] = 0;
            }
            if ($answerType === 'true') {
                $stats['points'] += 2;
            } elseif ($answerType === 'partial') {
                $stats['points'] += 1;
            }
            $stats[$answerType]++;
        }
        $stats['percentage'] = $stats['points'] / $stats['maxPoints'] * 100;
        return $stats;
    }

    /**
     * @return string[]
     */
    public function getGaugeStats(): array
    {
        $stats = ["true" => 0, "partial" => 0, "false" => 0, "not_answered" => 0];
        foreach ($this->getAnswers() as $answer) {
            $answerType = $answer->calculateAnswer();
            if ($answerType === null) {
                $answerType = 'not_answered';
            }
            if (isset($stats[$answerType])) {
                $stats[$answerType]++;
            } else {
                $stats[$answerType] = 1;
            }
        }
        return $stats;
    }

    /**
     * @return int
     */
    public function getCountAnswered(): int
    {
        $count = 0;
        foreach ($this->getAnswers() as $answer) {
            if ($answer->getAnswer() !== null) {
                $count++;
            }
        }
        return $count;
    }
}
