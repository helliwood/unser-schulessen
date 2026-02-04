<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 28.03.19
 * Time: 15:37
 */

namespace App\Entity\QualityCheck;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * MiniCheckAnswer Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(uniqueConstraints={
 *   @UniqueConstraint(columns={"question_id", "result_id"})
 * })
 * @ORM\Entity
 */
class MiniCheckAnswer
{
    public const ANSWER_TRUE = "true";
    public const ANSWER_PARTIAL = "partial";
    public const ANSWER_FALSE = "false";
    public const ANSWER_NOT_ANSWERED = null;

    public const ANSWER_LABELS = [
        self::ANSWER_TRUE => "Trifft zu",
        self::ANSWER_PARTIAL => "Trifft teilweise zu",
        self::ANSWER_FALSE => "Trifft nicht zu",
        self::ANSWER_NOT_ANSWERED => "Nicht beantwortet"
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Question")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     **/
    private ?Question $question = null;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private ?string $answer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\QualityCheck\MiniCheckResult", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private ?MiniCheckResult $result = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): MiniCheckAnswer
    {
        $this->id = $id;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): MiniCheckAnswer
    {
        $this->question = $question;
        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): MiniCheckAnswer
    {
        $this->answer = $answer;
        return $this;
    }

    public function getResult(): ?MiniCheckResult
    {
        return $this->result;
    }

    public function setResult(?MiniCheckResult $result): MiniCheckAnswer
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return string|null
     */
    public function calculateAnswer(): ?string
    {
        if ($this->getQuestion()->getType() === Question::TYPE_NEEDED && ! \is_null($this->getAnswer()) && \is_numeric($this->getAnswer())) {
            $testTrue = eval('return ' . $this->getAnswer() . ' ' . $this->getQuestion()->getFormula()->getFormulaTrue() . ';');
            $testFalse = eval('return ' . $this->getAnswer() . ' ' . $this->getQuestion()->getFormula()->getFormulaFalse() . ';');
            if ($testTrue) {
                return self::ANSWER_TRUE;
            } elseif ($testFalse) {
                return self::ANSWER_FALSE;
            }
            return self::ANSWER_PARTIAL;
        }
        return $this->getAnswer();
    }
}
