<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 09.08.19
 * Time: 13:37
 */

namespace App\Entity\QualityCircle;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ToDoItem-Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\QualityCircle\ToDoItemRepository")
 */
class ActionPlan implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    private $id;

    /**
     * @var ToDoItem
     * @ORM\OneToOne(targetEntity="App\Entity\QualityCircle\ToDoItem", inversedBy="actionPlan")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $todoItem;

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
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    private $who;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="date", name="`when`")
     */
    private $when;

    /**
     * @var \DateTime|null
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
     * ActionPlan constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->when = new \DateTime();
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
     * @return ActionPlan
     */
    public function setId(?int $id): ActionPlan
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return ToDoItem
     */
    public function getTodoItem(): ToDoItem
    {
        return $this->todoItem;
    }

    /**
     * @param ToDoItem $todoItem
     * @return ActionPlan
     */
    public function setTodoItem(ToDoItem $todoItem): ActionPlan
    {
        $this->todoItem = $todoItem;
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
     * @return ActionPlan
     */
    public function setWhat(?string $what): ActionPlan
    {
        $this->what = $what;
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
     * @return ActionPlan
     */
    public function setWho(?string $who): ActionPlan
    {
        $this->who = $who;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getWhen(): ?\DateTime
    {
        return $this->when;
    }

    /**
     * @param \DateTime|null $when
     * @return ActionPlan
     */
    public function setWhen(?\DateTime $when): ActionPlan
    {
        $this->when = $when;
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
     * @return ActionPlan
     */
    public function setCreatedAt(?\DateTime $createdAt): ActionPlan
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
     * @return ActionPlan
     */
    public function setCreatedBy(?User $createdBy): ActionPlan
    {
        $this->createdBy = $createdBy;
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
