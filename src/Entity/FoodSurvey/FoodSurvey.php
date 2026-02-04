<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 14:37
 */

namespace App\Entity\FoodSurvey;

use App\Entity\School;
use App\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FoodSurvey Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(name="food_survey")
 * @ORM\Entity(repositoryClass="App\Repository\FoodSurvey\FoodSurveyRepository")
 */
class FoodSurvey implements \JsonSerializable
{
    public const STATE_NOT_ACTIVATED = 0;
    public const STATE_ACTIVE = 1;
    public const STATE_CLOSED = 2;

    public const STATE_LABELS = [
        self::STATE_NOT_ACTIVATED => 'nicht gestartet',
        self::STATE_ACTIVE => 'Aktiv',
        self::STATE_CLOSED => 'Geschlossen',
    ];

    /**
     * The internal primary identity key.
     *
     * @var UuidInterface
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $uuid;

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private ?int $id;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", inversedBy="foodSurveys")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     **/
    private ?School $school;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="150")
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private string $name;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, options={"default" : 0})
     */
    private int $state = self::STATE_NOT_ACTIVATED;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $activatedAt = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $closesAt = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTime $createdAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    private ?User $createdBy = null;

    /**
     * @var ArrayCollection|Collection|FoodSurveyResult[]|null
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\FoodSurvey\FoodSurveyResult", cascade={"persist"}, mappedBy="foodSurvey")
     */
    private ?Collection $results;

    /**
     * @var ArrayCollection|Collection|FoodSurveySpot[]|null
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\FoodSurvey\FoodSurveySpot", cascade={"persist"}, mappedBy="foodSurvey")
     * @ORM\OrderBy({"order": "ASC"})
     */
    private ?Collection $spots;

    /**
     * Survey constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->createdAt = new DateTime();
        $this->spots = new ArrayCollection();
        $this->results = new ArrayCollection();
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid): FoodSurvey
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): FoodSurvey
    {
        $this->id = $id;
        return $this;
    }

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function setSchool(?School $school): FoodSurvey
    {
        $this->school = $school;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): FoodSurvey
    {
        $this->name = $name;
        return $this;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): FoodSurvey
    {
        $this->state = $state;
        return $this;
    }

    public function getActivatedAt(): ?DateTime
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(?DateTime $activatedAt): FoodSurvey
    {
        $this->activatedAt = $activatedAt;
        return $this;
    }

    public function getClosesAt(): ?DateTime
    {
        return $this->closesAt;
    }

    public function setClosesAt(?DateTime $closesAt): FoodSurvey
    {
        $this->closesAt = $closesAt;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): FoodSurvey
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): FoodSurvey
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getResults(): ?Collection
    {
        return $this->results;
    }

    public function setResults(?Collection $results): FoodSurvey
    {
        $this->results = $results;
        return $this;
    }

    /**
     * @return Collection|FoodSurveySpot[]|null
     */
    public function getSpots(): ?Collection
    {
        return $this->spots;
    }

    public function setSpots(?Collection $spots): FoodSurvey
    {
        $this->spots = $spots;
        return $this;
    }

    public function addSpot(FoodSurveySpot $foodSurveySpot): FoodSurvey
    {
        $foodSurveySpot->setFoodSurvey($this);
        $this->spots->add($foodSurveySpot);
        return $this;
    }

    public function reorderSpots(): FoodSurvey
    {
        $order = 1;
        foreach ($this->getSpots() as $spot) {
            $spot->setOrder($order);
            $order++;
        }
        return $this;
    }

    public function spotUp(FoodSurveySpot $foodSurveySpot): void
    {
        $lastSpot = null;
        foreach ($this->getSpots() as $spot) {
            if ($spot === $foodSurveySpot) {
                $foodSurveySpot->setOrder($foodSurveySpot->getOrder() - 1);
                $lastSpot->setOrder($lastSpot->getOrder() + 1);
            }
            $lastSpot = $spot;
        }
    }

    public function spotDown(FoodSurveySpot $foodSurveySpot): void
    {
        $found = false;
        foreach ($this->getSpots() as $spot) {
            if ($found) {
                $spot->setOrder($spot->getOrder() - 1);
                $found = false;
            }
            if ($spot === $foodSurveySpot) {
                $foodSurveySpot->setOrder($foodSurveySpot->getOrder() + 1);
                $found = true;
            }
        }
    }

    public function __clone()
    {
        $this->setUuid(Uuid::uuid4())
            ->setActivatedAt(null)
            ->setClosesAt(null)
            ->setCreatedAt(new DateTime())
            ->setState(0);

        $spots = $this->getSpots();
        $this->spots = new ArrayCollection();
        foreach ($spots as $spot) {
            $spotClone = clone $spot;
            $this->addSpot($spotClone);
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
            'activatedAt' => $this->getActivatedAt(),
            'numberOfParticipants' => $this->getResults()->count(),
            'closesAt' => $this->getClosesAt(),
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy() ? $this->getCreatedBy()->getDisplayName() : null,
            'spots' => $this->getSpots()->toArray()
        ];
    }
}
