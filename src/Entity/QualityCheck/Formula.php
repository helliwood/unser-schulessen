<?php

namespace App\Entity\QualityCheck;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Questionnaire Entity
 *
 * @author Victoria Köhring <köhring@helliwood.com>
 *
 * @ORM\Entity()
 */
class Formula implements \JsonSerializable
{

    /**
     * @var Question
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="\App\Entity\QualityCheck\Question", inversedBy="formula", cascade={"persist"})
     * @ORM\JoinColumn(name="question_id", onDelete="CASCADE")
     */
    private $question;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=250, unique=false, nullable=false)
     */
    private $formula_true;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=250, unique=false, nullable=false)
     */
    private $formula_false;

    /**
     * @return Question|null
     */
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    /**
     * @param Question $question
     * @return Formula
     */
    public function setQuestion(Question $question): Formula
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormulaTrue(): ?string
    {
        return $this->formula_true;
    }

    /**
     * @param string|null $formula_true
     * @return Formula
     */
    public function setFormulaTrue(?string $formula_true): Formula
    {
        $this->formula_true = $formula_true;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormulaFalse(): ?string
    {
        return $this->formula_false;
    }

    /**
     * @param string|null $formula_false
     * @return Formula
     */
    public function setFormulaFalse(?string $formula_false): Formula
    {
        $this->formula_false = $formula_false;
        return $this;
    }


    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'formula_true' => $this->getFormulaTrue(),
            'formula_false' => $this->getFormulaFalse(),
            'formula_partial' => $this->getFormulaPartial()

        ];
    }
}
