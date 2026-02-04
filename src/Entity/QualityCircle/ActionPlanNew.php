<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 09.08.19
 * Time: 13:37
 */

namespace App\Entity\QualityCircle;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ActionPlanNew-Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity()
 */
class ActionPlanNew implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var ToDoNew
     * @ORM\ManyToOne(targetEntity="App\Entity\QualityCircle\ToDoNew", inversedBy="actionPlans")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $toDo;

    /**
     *
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    private $what;

    /**
     *
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(max="2048")
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    private $how;

    /**
     *
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    private $who;

    /**
     * @var DateTime|null
     * @ORM\Column(type="date", name="`when`")
     */
    private $when;

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
     * @var bool|null
     * @Assert\Type("bool")
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $completed;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $closed = false;

    /**
     * @var string|null
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $note;

    /**
     * ActionPlan constructor.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->when = new DateTime();
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
     * @return ActionPlanNew
     */
    public function setId(?int $id): ActionPlanNew
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return ToDoNew
     */
    public function getToDo(): ToDoNew
    {
        return $this->toDo;
    }

    /**
     * @param ToDoNew $toDo
     * @return ActionPlanNew
     */
    public function setToDo(ToDoNew $toDo): ActionPlanNew
    {
        $this->toDo = $toDo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWhat(): ?string
    {
        return $this->what;
    }

    /**
     * @param string|null $what
     * @return ActionPlanNew
     */
    public function setWhat(?string $what): ActionPlanNew
    {
        $this->what = $what;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHow(): ?string
    {
        return $this->how;
    }

    /**
     * @param string|null $how
     * @return ActionPlanNew
     */
    public function setHow(?string $how): ActionPlanNew
    {
        $this->how = $how;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWho(): ?string
    {
        return $this->who;
    }

    /**
     * @param string|null $who
     * @return ActionPlanNew
     */
    public function setWho(?string $who): ActionPlanNew
    {
        $this->who = $who;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getWhen(): ?DateTime
    {
        return $this->when;
    }

    /**
     * @param DateTime|null $when
     * @return ActionPlanNew
     */
    public function setWhen(?DateTime $when): ActionPlanNew
    {
        $this->when = $when;
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
     * @return ActionPlanNew
     */
    public function setCreatedAt(?DateTime $createdAt): ActionPlanNew
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
     * @return ActionPlanNew
     */
    public function setCreatedBy(?User $createdBy): ActionPlanNew
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    /**
     * @param bool|null $completed
     * @return ActionPlanNew
     */
    public function setCompleted(?bool $completed): ActionPlanNew
    {
        $this->completed = $completed;
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
     * @return ActionPlanNew
     */
    public function setClosed(bool $closed): ActionPlanNew
    {
        $this->closed = $closed;
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
     * @return ActionPlanNew
     */
    public function setNote(?string $note): ActionPlanNew
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'what' => $this->getWhat(),
            'who' => $this->getWho(),
            'when' => $this->getWhen(),
            'created_at' => $this->getCreatedAt(),
            'created_by' => $this->getCreatedBy()->getDisplayName()
        ];
    }
}
