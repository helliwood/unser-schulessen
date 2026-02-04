<?php

namespace App\Entity\QualityCheck;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * Ideabox Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\IdeaboxRepository")
 * @ORM\Table()
 * @IgnoreAnnotation("phpcsSuppress")
 */
class Ideabox implements \JsonSerializable
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024)
     */
    private $idea;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false, name="`order`")
     */
    private $order;

    /**
     * @var Question
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Question", inversedBy="ideaboxes")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $question;

    /**
     * @var IdeaboxIcon[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="\App\Entity\QualityCheck\IdeaboxIcon", inversedBy="ideabox")
     * @JoinTable(name="ideaboxes_ideabox_icons",
     *      joinColumns={@JoinColumn(name="ideabox_icon_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="ideabox_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     * @ORM\OrderBy({"id":"ASC"})
     */
    private $ideaboxIcons;

    /**
     * @var Ideabox
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\QualityCheck\Ideabox")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $previous;

    /**
     * Ideabox Constructor
     */
    public function __construct()
    {
        $this->ideaboxIcons = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIdea(): ?string
    {
        return $this->idea;
    }

    /**
     * @param string $idea
     * @return Ideabox
     */
    public function setIdea(string $idea): self
    {
        $this->idea = $idea;

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
     * @return Ideabox
     */
    public function setOrder(int $order): Ideabox
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return Question
     */
    public function getQuestion(): Question
    {
        return $this->question;
    }

    /**
     * @param Question $question
     * @return Ideabox
     */
    public function setQuestion(Question $question): Ideabox
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return IdeaboxIcon[]|ArrayCollection
     */
    public function getIdeaboxIcons()
    {
        return $this->ideaboxIcons;
    }

    /**
     * @param IdeaboxIcon[]|ArrayCollection $ideaboxIcons
     * @return Ideabox
     */
    public function setIdeaboxIcons($ideaboxIcons): Ideabox
    {
        $this->ideaboxIcons = $ideaboxIcons;
        return $this;
    }

    /**
     * @param IdeaboxIcon $ideaboxIcon
     * @return Ideabox[]|ArrayCollection
     */
    public function addIdeaboxIcon(IdeaboxIcon $ideaboxIcon): void
    {
        $this->ideaboxIcons[] = $ideaboxIcon;
    }

    /**
     * @return Ideabox
     */
    public function getPrevious(): Ideabox
    {
        return $this->previous;
    }

    /**
     * @param Ideabox $previous
     * @return Ideabox
     */
    public function setPrevious(Ideabox $previous): Ideabox
    {
        $this->previous = $previous;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->getOrder() === 1;
    }

    /**
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->getQuestion()->getIdeaboxes()->count() === $this->getOrder();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->idea;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'order' => $this->getOrder(),
            'idea' => $this->getIdea(),
            'first' => $this->isFirst(),
            'last' => $this->isLast(),
            'ideaboxIcons' => $this->ideaboxIcons->getValues()
        ];
    }
}
