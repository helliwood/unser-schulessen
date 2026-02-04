<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 14:37
 */

namespace App\Entity\Survey;

use App\Entity\School;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Survey Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="survey_survey")
 * @ORM\Entity(repositoryClass="App\Repository\Survey\SurveyRepository")
 */
class Survey implements \JsonSerializable
{
    public const STATE_NOT_ACTIVATED = 0;
    public const STATE_ACTIVE = 1;
    public const STATE_CLOSED = 2;

    public const STATE_LABELS = [
        self::STATE_NOT_ACTIVATED => 'nicht gestartet',
        self::STATE_ACTIVE => 'Aktiv',
        self::STATE_CLOSED => 'Geschlossen',
    ];

    public const TYPE_OPEN = 'open';
    public const TYPE_VOUCHER = 'voucher';

    public const TYPE_LABELS = [
        self::TYPE_OPEN => 'Offen',
        self::TYPE_VOUCHER => 'Voucher'
    ];

    /**
     * The internal primary identity key.
     *
     * @var UuidInterface
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", inversedBy="surveys")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $school;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="150")
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="50")
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, options={"default" : 0})
     */
    private $state = self::STATE_NOT_ACTIVATED;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $activatedAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closesAt;

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
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $numberOfParticipants = 0;

    /**
     * @var ArrayCollection|SurveyQuestion[]
     * @ORM\OneToMany(targetEntity="\App\Entity\Survey\SurveyQuestion", cascade={"persist"}, mappedBy="survey", orphanRemoval=true)
     * @ORM\OrderBy({"order":"ASC"})
     */
    private $questions;

    /**
     * @var SurveyVoucher[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\Survey\SurveyVoucher", mappedBy="survey")
     */
    private $vouchers;

    /**
     * virtual field for create form
     * @var int
     */
    private $numberOfVoucher = 0;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true, options={"default":false})
     */
    private $surveyTemplate = false;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $introduction = null;

    /**
     * Survey constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->questions = new ArrayCollection();
        $this->vouchers = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * @return UuidInterface|null
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface|null $uuid
     * @return Survey
     */
    public function setUuid(UuidInterface $uuid): Survey
    {
        $this->uuid = $uuid;
        return $this;
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
     * @return Survey
     */
    public function setId(?int $id): Survey
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return School|null
     */
    public function getSchool(): ?School
    {
        return $this->school;
    }

    /**
     * @param School|null $school
     * @return Survey
     */
    public function setSchool(?School $school): Survey
    {
        $this->school = $school;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Survey
     */
    public function setName(string $name): Survey
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     * @return Survey
     */
    public function setState(int $state): Survey
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Survey
     */
    public function setType(string $type): Survey
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getActivatedAt(): ?\DateTime
    {
        return $this->activatedAt;
    }

    /**
     * @param \DateTime|null $activatedAt
     * @return Survey
     */
    public function setActivatedAt(?\DateTime $activatedAt): Survey
    {
        $this->activatedAt = $activatedAt;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getClosesAt(): ?\DateTime
    {
        return $this->closesAt;
    }

    /**
     * @param \DateTime|string|null $closesAt
     * @return Survey
     * @throws \Exception
     */
    public function setClosesAt($closesAt): Survey
    {
        if (\is_string($closesAt)) {
            $closesAt = new \DateTime($closesAt);
        }
        $this->closesAt = $closesAt;
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
     * @return Survey
     */
    public function setCreatedAt(?\DateTime $createdAt): Survey
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
     * @return Survey
     */
    public function setCreatedBy(?User $createdBy): Survey
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfParticipants(): int
    {
        return $this->numberOfParticipants;
    }

    /**
     * @param int $numberOfParticipants
     * @return Survey
     */
    public function setNumberOfParticipants(int $numberOfParticipants): Survey
    {
        $this->numberOfParticipants = $numberOfParticipants;
        return $this;
    }

    /**
     * @return SurveyQuestion[]|ArrayCollection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param SurveyQuestion $surveyQuestion
     * @return Survey
     */
    public function addQuestion(SurveyQuestion $surveyQuestion): Survey
    {
        $surveyQuestion->setSurvey($this);
        $this->questions->add($surveyQuestion);
        return $this;
    }

    /**
     * @param SurveyQuestion $surveyQuestion
     * @return Survey
     */
    public function removeQuestion(SurveyQuestion $surveyQuestion): Survey
    {
        if ($this->questions->contains($surveyQuestion)) {
            $this->questions->removeElement($surveyQuestion);
        }
        return $this;
    }

    /**
     * @return SurveyVoucher[]|ArrayCollection
     */
    public function getVouchers()
    {
        return $this->vouchers;
    }

    /**
     * @param SurveyVoucher[]|ArrayCollection $vouchers
     * @return Survey
     */
    public function setVouchers($vouchers): Survey
    {
        $this->vouchers = $vouchers;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfVoucher(): int
    {
        return $this->numberOfVoucher;
    }

    /**
     * @return bool|null
     */
    public function getSurveyTemplate(): ?bool
    {
        return $this->surveyTemplate;
    }

    /**
     * @param bool|null $surveyTemplate
     * @return Survey
     */
    public function setSurveyTemplate(?bool $surveyTemplate): Survey
    {
        $this->surveyTemplate = $surveyTemplate;
        return $this;
    }

    /**
     * @param int $numberOfVoucher
     * @return Survey
     */
    public function setNumberOfVoucher(int $numberOfVoucher): Survey
    {
        $this->numberOfVoucher = $numberOfVoucher;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    /**
     * @param string|null $introduction
     * @return Survey
     */
    public function setIntroduction(?string $introduction): Survey
    {
        $this->introduction = $introduction;
        return $this;
    }


    /**
     * @throws Exception
     */
    public function __clone()
    {
        $this->setUuid(Uuid::uuid4())
            ->setActivatedAt(null)
            ->setClosesAt(null)
            ->setNumberOfParticipants(0)
            ->setCreatedAt(new \DateTime())
            ->setState(0);
        $questions = $this->getQuestions();
        $this->questions = new ArrayCollection();
        foreach ($questions as $question) {
            $questionClone = clone $question;
            $this->addQuestion($questionClone);
        }
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'id' => $this->getId(),
            'name' => $this->getName(),
            'state' => $this->getState(),
            'stateLabel' => self::STATE_LABELS[$this->getState()],
            'type' => $this->getType(),
            'typeLabel' => self::TYPE_LABELS[$this->getType()] ?? null,
            'questions' => $this->questions->count(),
            'activatedAt' => $this->getActivatedAt(),
            'numberOfParticipants' => $this->getNumberOfParticipants(),
            'closesAt' => $this->getClosesAt(),
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy() ? $this->getCreatedBy()->getDisplayName() : null
        ];
    }
}
