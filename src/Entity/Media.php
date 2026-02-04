<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-01-13
 * Time: 10:10
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Media Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\MediaRepository")
 */
class Media implements \JsonSerializable
{
    /**
     *
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected $id;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", cascade={"persist"}, fetch="EAGER", inversedBy="masterData")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $school;

    /**
     *
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", length=250, nullable=false)
     */
    protected $fileName;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    protected $mimeType;

    /**
     *
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fileSize;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected $createdBy;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false,options={"default":0})
     */
    protected $directory = false;

    /**
     * @var Media|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\Media", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var Media[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\Media", mappedBy="parent", indexBy="id")
     * @ORM\OrderBy({"directory":"DESC"})
     * @ORM\OrderBy({"fileName":"ASC"})
     */
    protected $children;

    /**
     * MasterData constructor.
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
     * @return Media
     */
    public function setId(?int $id): Media
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
     * @return Media
     */
    public function setSchool(?School $school): Media
    {
        $this->school = $school;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return Media
     */
    public function setDescription(?string $description): Media
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @param string|null $fileName
     * @return Media
     */
    public function setFileName(?string $fileName): Media
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     * @return Media
     */
    public function setMimeType(?string $mimeType): Media
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    /**
     * @param int|null $fileSize
     * @return Media
     */
    public function setFileSize(?int $fileSize): Media
    {
        $this->fileSize = $fileSize;
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
     * @return Media
     */
    public function setCreatedAt(?\DateTime $createdAt): Media
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
     * @return Media
     */
    public function setCreatedBy(?User $createdBy): Media
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDirectory(): bool
    {
        return $this->directory;
    }

    /**
     * @param bool $directory
     */
    public function setDirectory(bool $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * @return Media|null
     */
    public function getParent(): ?Media
    {
        return $this->parent;
    }

    /**
     * @param Media|null $parent
     */
    public function setParent(?Media $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Media[]|ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Media[]|ArrayCollection $children
     */
    public function setChildren($children): void
    {
        $this->children = $children;
    }
    
    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt(),
            'createdBy' => $this->getCreatedBy()->getDisplayName(),
            'fileName' => $this->getFileName(),
            'fileSize' => $this->getFileSize(),
            'mimeType' => $this->getMimeType(),
            'description' => $this->getDescription(),
            'parent' => $this->getParent(),
            'directory' => $this->isDirectory(),
        ];
    }
}
