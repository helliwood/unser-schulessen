<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 09.08.19
 * Time: 13:37
 */

namespace App\Entity\QualityCircle;

use App\Entity\QualityCheck\Answer;
use App\Entity\School;
use App\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ToDoNew-Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\QualityCircle\ToDoNewRepository")
 */
class ToDoNew implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @Assert\Length(max="2048")
     * @ORM\Column(type="string", length=2048, nullable=true)
     */
    private $description;

    /**
     * @var bool|null
     * @Assert\Type("bool")
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $completed;

    /**
     * @var string|null
     * @Assert\Length(max="2048")
     * @ORM\Column(type="string", length=2048, nullable=true)
     */
    private $note;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $school;

    /**
     * @var Answer|null
     * @ORM\ManyToOne(targetEntity="App\Entity\QualityCheck\Answer")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT", nullable=true)
     */
    private $answer;

    /**
     * @var ActionPlanNew[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCircle\ActionPlanNew", mappedBy="toDo", cascade={"persist"}, fetch="EAGER")
     */
    private $actionPlans;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private $createdBy;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $closed = false;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT", nullable=true)
     */
    private $closedBy;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->actionPlans = new ArrayCollection();
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
     * @return ToDoNew
     */
    public function setId(?int $id): ToDoNew
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ToDoNew
     */
    public function setName(string $name): ToDoNew
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param ?string $description
     * @return ToDoNew
     */
    public function setDescription(?string $description): ToDoNew
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    /**
     * @param bool|null $completed
     * @return ToDoNew
     */
    public function setCompleted(?bool $completed): ToDoNew
    {
        $this->completed = $completed;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     * @return ToDoNew
     */
    public function setNote(?string $note): ToDoNew
    {
        $this->note = $note;
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
     * @return ToDoNew
     */
    public function setSchool(?School $school): ToDoNew
    {
        $this->school = $school;
        return $this;
    }

    /**
     * @return Answer|null
     */
    public function getAnswer(): ?Answer
    {
        return $this->answer;
    }

    /**
     * @param Answer|null $answer
     * @return ToDoNew
     */
    public function setAnswer(?Answer $answer): ToDoNew
    {
        $this->answer = $answer;
        return $this;
    }

    /**
     * @return ActionPlanNew[]|ArrayCollection
     */
    public function getActionPlans()
    {
        return $this->actionPlans;
    }

    /**
     * @param ActionPlanNew[]|ArrayCollection $actionPlans
     * @return ToDoNew
     */
    public function setActionPlans($actionPlans): ToDoNew
    {
        $this->actionPlans = $actionPlans;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     * @return ToDoNew
     */
    public function setCreatedAt(?DateTime $createdAt): ToDoNew
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
     * @return ToDoNew
     */
    public function setCreatedBy(?User $createdBy): ToDoNew
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @param bool $closed
     * @return ToDoNew
     */
    public function setClosed(bool $closed): ToDoNew
    {
        $this->closed = $closed;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    /**
     * @param DateTime|null $closedAt
     * @return ToDoNew
     */
    public function setClosedAt(?DateTime $closedAt): ToDoNew
    {
        $this->closedAt = $closedAt;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    /**
     * @param User|null $closedBy
     * @return ToDoNew
     */
    public function setClosedBy(?User $closedBy): ToDoNew
    {
        $this->closedBy = $closedBy;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getStats(): array
    {
        $stats = ['action_plans' => 0, 'action_plans_completed' => 0, 'action_plans_open' => 0];
        foreach ($this->getActionPlans() as $actionPlan) {
            $stats['action_plans']++;
            if ($actionPlan->isCompleted()) {
                $stats['action_plans_completed']++;
            }
            if (! $actionPlan->isClosed()) {
                $stats['action_plans_open']++;
            }
        }
        return $stats;
    }

    /**
     * @return bool
     */
    public function allActionPlansCompleted(): bool
    {
        foreach ($this->getActionPlans() as $actionPlan) {
            if (! $actionPlan->isCompleted()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isClosable(): bool
    {
        if ($this->getActionPlans()->count() <= 0) {
            return false;
        }
        foreach ($this->getActionPlans() as $actionPlan) {
            if (! $actionPlan->isClosed()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return \array_merge([
            'id' => $this->getId(),
            'name' => $this->getName(),
            'question' => $this->getAnswer() ? $this->getAnswer()->getQuestion() : null,
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy()->getDisplayName(),
            'completed' => $this->getCompleted(),
            'closed' => $this->isClosed(),
            'closedAt' => $this->getClosedAt(),
            'closedBy' => $this->getClosedBy() ? $this->getClosedBy()->getDisplayName() : null
        ], $this->getStats());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
