<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 29.08.19
 * Time: 15:45
 */

namespace App\Entity\Survey;

use Doctrine\ORM\Mapping as ORM;

/**
 * Survey Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="survey_surveyquestion_choice_answer")
 * @ORM\Entity()
 */
class SurveyQuestionChoiceAnswer
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
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\SurveyQuestion", inversedBy="answers", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $question;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var SurveyVoucher|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\SurveyVoucher", inversedBy="answers", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private $voucher;

    /**
     * @var SurveyQuestionChoice|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\SurveyQuestionChoice", inversedBy="answers", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $choice;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $userAgent;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $userIp;

    /**
     * SurveyQuestion constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * @return SurveyQuestionChoiceAnswer
     */
    public function setId(?int $id): SurveyQuestionChoiceAnswer
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return SurveyQuestion
     */
    public function getQuestion(): SurveyQuestion
    {
        return $this->question;
    }

    /**
     * @param SurveyQuestion $question
     * @return SurveyQuestionChoiceAnswer
     */
    public function setQuestion(SurveyQuestion $question): SurveyQuestionChoiceAnswer
    {
        $this->question = $question;
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
     * @return SurveyQuestionChoiceAnswer
     */
    public function setCreatedAt(?\DateTime $createdAt): SurveyQuestionChoiceAnswer
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return SurveyVoucher|null
     */
    public function getVoucher(): ?SurveyVoucher
    {
        return $this->voucher;
    }

    /**
     * @param SurveyVoucher|null $voucher
     * @return SurveyQuestionChoiceAnswer
     */
    public function setVoucher(?SurveyVoucher $voucher): SurveyQuestionChoiceAnswer
    {
        $this->voucher = $voucher;
        return $this;
    }

    /**
     * @return SurveyQuestionChoice|null
     */
    public function getChoice(): ?SurveyQuestionChoice
    {
        return $this->choice;
    }

    /**
     * @param SurveyQuestionChoice|null $choice
     * @return SurveyQuestionChoiceAnswer
     */
    public function setChoice(?SurveyQuestionChoice $choice): SurveyQuestionChoiceAnswer
    {
        $this->choice = $choice;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @param string|null $userAgent
     * @return SurveyQuestionChoiceAnswer
     */
    public function setUserAgent(?string $userAgent): SurveyQuestionChoiceAnswer
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    /**
     * @param string|null $userIp
     * @return SurveyQuestionChoiceAnswer
     */
    public function setUserIp(?string $userIp): SurveyQuestionChoiceAnswer
    {
        $this->userIp = $userIp;
        return $this;
    }
}
