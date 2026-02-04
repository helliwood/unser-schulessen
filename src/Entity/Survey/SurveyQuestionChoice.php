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
 * @ORM\Table(name="survey_surveyquestion_choice")
 * @ORM\Entity()
 */
class SurveyQuestionChoice
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
     * @var SurveyQuestion
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\SurveyQuestion", inversedBy="choices", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $question;

    /**
     *
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $choice;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var ArrayCollection|SurveyQuestionChoiceAnswer[]
     * @ORM\OneToMany(targetEntity="App\Entity\Survey\SurveyQuestionChoiceAnswer", mappedBy="choice")
     */
    private $answers;

    /**
     * SurveyQuestion constructor.
     */
    public function __construct()
    {
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
    public function setId(?int $id): SurveyQuestionChoice
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return SurveyQuestion
     */
    public function getQuestion(): SurveyQuestionChoice
    {
        return $this->question;
    }

    /**
     * @param SurveyQuestion $question
     * @return SurveyQuestionChoice
     */
    public function setQuestion(SurveyQuestion $question): SurveyQuestionChoice
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getChoice(): ?string
    {
        return $this->choice;
    }

    /**
     * @param string|null $choice
     * @return SurveyQuestionChoice
     */
    public function setChoice(?string $choice): SurveyQuestionChoice
    {
        $this->choice = $choice;
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
     * @return SurveyQuestionChoice
     */
    public function setOrder(int $order): SurveyQuestionChoice
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return SurveyQuestionChoiceAnswer[]|ArrayCollection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param SurveyQuestionChoiceAnswer[]|ArrayCollection $answers
     * @return SurveyQuestionChoice
     */
    public function setAnswers($answers): SurveyQuestionChoice
    {
        $this->answers = $answers;
        return $this;
    }

    public function __clone()
    {
        $this->setId(null)
            ->setAnswers(new ArrayCollection());
    }
}
