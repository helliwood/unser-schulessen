<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 29.08.19
 * Time: 14:37
 */

namespace App\Entity\Survey;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SurveyVoucher Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="survey_survey_voucher")
 * @ORM\Entity(repositoryClass="App\Repository\Survey\SurveyVoucherRepository")
 */
class SurveyVoucher implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var Survey
     * @ORM\ManyToOne(targetEntity="\App\Entity\Survey\Survey", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $survey;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="150")
     * @ORM\Column(type="string", length=50, nullable=false, unique=true, options={"collation":"utf8mb4_bin"})
     */
    private $voucher;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    private $createdBy;

    /**
     * @var ArrayCollection|SurveyQuestionAnswer[]
     * @ORM\OneToMany(targetEntity="App\Entity\Survey\SurveyQuestionAnswer", mappedBy="voucher")
     */
    private $answers;

    /**
     * SurveyVoucher constructor.
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
     * @return SurveyVoucher
     */
    public function setId(?int $id): SurveyVoucher
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Survey|null
     */
    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    /**
     * @param Survey|null $survey
     * @return SurveyVoucher
     */
    public function setSurvey(?Survey $survey): SurveyVoucher
    {
        $this->survey = $survey;
        return $this;
    }

    /**
     * @return string
     */
    public function getVoucher(): string
    {
        return $this->voucher;
    }

    /**
     * @param string $voucher
     * @return SurveyVoucher
     */
    public function setVoucher(string $voucher): SurveyVoucher
    {
        $this->voucher = $voucher;
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
     * @return SurveyVoucher
     */
    public function setCreatedAt(?\DateTime $createdAt): SurveyVoucher
    {
        $this->createdAt = $createdAt;
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
     * @return SurveyVoucher
     */
    public function setCreatedBy(?User $createdBy): SurveyVoucher
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return SurveyQuestionAnswer[]|ArrayCollection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param SurveyQuestionAnswer[]|ArrayCollection $answers
     * @return SurveyVoucher
     */
    public function setAnswers($answers): SurveyVoucher
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->answers->count() > 0;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'voucher' => $this->getVoucher(),
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy() ? $this->getCreatedBy()->getDisplayName() : null,
            'inUse' => $this->isInUse()
        ];
    }
}
