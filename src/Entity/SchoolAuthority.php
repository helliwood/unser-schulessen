<?php

namespace App\Entity;

use App\Entity\Survey\Survey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\SchoolAuthorityRepository::class)]
class SchoolAuthority implements \JsonSerializable
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private $id;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private $name;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    /**
     * @var Collection|School[]
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\School::class, mappedBy: 'schoolAuthority')]
    private $schools;

    /**
     * @var Collection|Survey[]
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Survey\Survey::class, mappedBy: 'schoolAuthority')]
    private $surveys;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $contactPerson;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $contactEmail;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $contactPhone;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    public function __construct()
    {
        $this->schools = new ArrayCollection();
        $this->surveys = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSchools(): Collection
    {
        return $this->schools;
    }

    public function getSurveys(): Collection
    {
        return $this->surveys;
    }

    public function addSchool(School $school): self
    {
        if (! $this->schools->contains($school)) {
            $this->schools[] = $school;
            $school->setSchoolAuthority($this);
        }
        return $this;
    }

    public function removeSchool(School $school): self
    {
        if ($this->schools->contains($school)) {
            $this->schools->removeElement($school);
            if ($school->getSchoolAuthority() === $this) {
                $school->setSchoolAuthority(null);
            }
        }
        return $this;
    }

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $contactPerson): self
    {
        $this->contactPerson = $contactPerson;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'contactPerson' => $this->contactPerson,
            'contactEmail' => $this->contactEmail,
            'contactPhone' => $this->contactPhone,
            'createdAt' => $this->createdAt
        ];
    }
}
