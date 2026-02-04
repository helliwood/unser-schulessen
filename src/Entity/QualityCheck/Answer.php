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
 * Answer Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(uniqueConstraints={
 *   @UniqueConstraint(columns={"question_id", "result_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\QualityCheck\AnswerRepository")
 */
class Answer
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
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var Question|null
     * @ORM\ManyToOne(targetEntity="Question")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     **/
    private $question;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private $answer;

    /**
     * @var Result|null
     * @ORM\ManyToOne(targetEntity="Result", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $result;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Answer
     */
    public function setId(?int $id): Answer
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Question|null
     */
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    /**
     * @param Question|null $question
     * @return Answer
     */
    public function setQuestion(?Question $question): Answer
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    /**
     * @param string|null $answer
     * @return Answer
     */
    public function setAnswer(?string $answer): Answer
    {
        $this->answer = $answer;
        return $this;
    }

    /**
     * @return Result|null
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * @param Result|null $result
     * @return Answer
     */
    public function setResult(?Result $result): Answer
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
