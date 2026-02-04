<?php

namespace App\Entity\QualityCheck;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * IdeaboxIcon Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\IdeaboxIconRepository")
 * @IgnoreAnnotation("phpcsSuppress")

 */
class IdeaboxIcon implements \JsonSerializable
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
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255)
     */
    private $category;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255)
     */
    private $icon;

    /**
     * @var Ideabox
     * @ORM\ManyToMany(targetEntity="\App\Entity\QualityCheck\Ideabox", mappedBy="ideaboxIcons")
     */
    private $ideabox;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $order;

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
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return IdeaboxIcon
     */
    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return IdeaboxIcon
     */
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return Ideabox
     */
    public function getIdeabox(): Ideabox
    {
        return $this->ideabox;
    }

    /**
     * @param IdeaboxIcon $ideaboxIcon
     * @return Ideabox
     */
    public function setIdeabox(Ideabox $ideabox): IdeaboxIcon
    {
        $this->ideabox = $ideabox;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): IdeaboxIcon
    {
        $this->order = $order;
        return $this;
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->category;
    }


    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'category' => $this->getCategory(),
            'icon' => $this->getIcon()
        ];
    }
}
