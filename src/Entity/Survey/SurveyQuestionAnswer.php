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
 * @ORM\Table(name="survey_surveyquestion_answer")
 * @ORM\Entity()
 */
class SurveyQuestionAnswer
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
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $answer;

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
     * @return SurveyQuestionAnswer
     */
    public function setId(?int $id): SurveyQuestionAnswer
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
     * @return SurveyQuestionAnswer
     */
    public function setQuestion(SurveyQuestion $question): SurveyQuestionAnswer
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
     * @return SurveyQuestionAnswer
     */
    public function setCreatedAt(?\DateTime $createdAt): SurveyQuestionAnswer
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
     * @return SurveyQuestionAnswer
     */
    public function setVoucher(?SurveyVoucher $voucher): SurveyQuestionAnswer
    {
        $this->voucher = $voucher;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAnswer(): ?bool
    {
        return $this->answer;
    }

    /**
     * @param bool|null $answer
     * @return SurveyQuestionAnswer
     */
    public function setAnswer(?bool $answer): SurveyQuestionAnswer
    {
        $this->answer = $answer;
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
     * @return SurveyQuestionAnswer
     */
    public function setUserAgent(?string $userAgent): SurveyQuestionAnswer
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
     * @return SurveyQuestionAnswer
     */
    public function setUserIp(?string $userIp): SurveyQuestionAnswer
    {
        $this->userIp = $userIp;
        return $this;
    }
}
