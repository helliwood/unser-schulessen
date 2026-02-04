<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 14:30
 */

namespace App\Entity;

use App\Entity\FoodSurvey\FoodSurvey;
use App\Entity\QualityCheck\Result;
use App\Entity\Survey\Survey;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SchoolRepository")
 * @IgnoreAnnotation("phpcsSuppress")
 */
class School implements \JsonSerializable
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $schoolNumber;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $headmaster;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $faxNumber;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emailAddress;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $webpage;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $educationAuthority;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $schoolType;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $schoolOperator;

    /**
     * @var string|null
     * @Assert\Length(max="1024")
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $particularity;

    /**
     * @var Address|null
     *
     * @Assert\Valid()
     * @ORM\OneToOne(targetEntity="\App\Entity\Address", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private $address;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var Person[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="Person", mappedBy="school")
     **/
    private $persons;

    /**
     * @var UserHasSchool[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="UserHasSchool", mappedBy="school")
     */
    private $userHasSchool;

    /**
     * @var MasterData[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="MasterData", mappedBy="school")
     */
    private $masterData;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $auditEnd;

    /**
     * @var User|null
     */
    private $consultant;

    /**
     * @var string[] Zusätzliche beschreibende Flags für die Schulen
     * @ORM\Column(type="json", nullable=true)
     */
    private $flags = null;

    /**
     *
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $miniCheck = false;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $miniCheckName;

    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $miniCheckEmail;

    /**
     * @var Collection|Result[]
     * @ORM\OneToMany(targetEntity="\App\Entity\QualityCheck\Result", mappedBy="school", cascade={"persist", "remove"})
     */
    private $results;

    /**
     * @var Collection|Result[]
     * @ORM\OneToMany(targetEntity="\App\Entity\Survey\Survey", mappedBy="school", cascade={"persist", "remove"})
     */
    private $surveys;

    /**
     * @var Collection|Result[]
     * @ORM\OneToMany(targetEntity="\App\Entity\FoodSurvey\FoodSurvey", mappedBy="school", cascade={"persist", "remove"})
     */
    private $foodSurveys;

    /**
     * School constructor.
     */
    public function __construct()
    {
        $this->address = new Address();
        $this->createdAt = new \DateTime();
        $this->userHasSchool = new ArrayCollection();
        $this->masterData = new ArrayCollection();
        $this->results = new ArrayCollection();
        $this->foodResults = new ArrayCollection();
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
     * @return School
     */
    public function setId(?int $id): School
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSchoolNumber(): ?string
    {
        return $this->schoolNumber;
    }

    /**
     * @param string|null $schoolNumber
     * @return School
     */
    public function setSchoolNumber(?string $schoolNumber): School
    {
        $this->schoolNumber = $schoolNumber;
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
     * @param string|null $name
     * @return School
     */
    public function setName(?string $name): School
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHeadmaster(): ?string
    {
        return $this->headmaster;
    }

    /**
     * @param string|null $headmaster
     * @return School
     */
    public function setHeadmaster(?string $headmaster): School
    {
        $this->headmaster = $headmaster;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     * @return School
     */
    public function setPhoneNumber(?string $phoneNumber): School
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    /**
     * @param string|null $faxNumber
     * @return School
     */
    public function setFaxNumber(?string $faxNumber): School
    {
        $this->faxNumber = $faxNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    /**
     * @param string|null $emailAddress
     * @return School
     */
    public function setEmailAddress(?string $emailAddress): School
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebpage(): ?string
    {
        return $this->webpage;
    }

    /**
     * @param string|null $webpage
     * @return School
     */
    public function setWebpage(?string $webpage): School
    {
        $this->webpage = $webpage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEducationAuthority(): ?string
    {
        return $this->educationAuthority;
    }

    /**
     * @param string|null $educationAuthority
     * @return School
     */
    public function setEducationAuthority(?string $educationAuthority): School
    {
        $this->educationAuthority = $educationAuthority;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSchoolType(): ?string
    {
        return $this->schoolType;
    }

    /**
     * @param string|null $schoolType
     * @return School
     */
    public function setSchoolType(?string $schoolType): School
    {
        $this->schoolType = $schoolType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSchoolOperator(): ?string
    {
        return $this->schoolOperator;
    }

    /**
     * @param string|null $schoolOperator
     * @return School
     */
    public function setSchoolOperator(?string $schoolOperator): School
    {
        $this->schoolOperator = $schoolOperator;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getParticularity(): ?string
    {
        return $this->particularity;
    }

    /**
     * @param string|null $particularity
     * @return School
     */
    public function setParticularity(?string $particularity): School
    {
        $this->particularity = $particularity;
        return $this;
    }

    /**
     * @return Address|null
     */
    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /**
     * @param Address|null $address
     * @return School
     */
    public function setAddress(?Address $address): School
    {
        $this->address = $address;
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
     * @return School
     */
    public function setCreatedAt(?\DateTime $createdAt): School
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Person[]|ArrayCollection
     */
    public function getPersons()
    {
        return $this->persons;
    }

    /**
     * @param Person[]|ArrayCollection $persons
     * @return School
     */
    public function setPersons($persons): School
    {
        $this->persons = $persons;
        return $this;
    }

    /**
     * @return UserHasSchool[]|ArrayCollection
     */
    public function getUserHasSchool()
    {
        return $this->userHasSchool;
    }

    /**
     * @param UserHasSchool[]|ArrayCollection $userHasSchool
     * @return School
     */
    public function setUserHasSchool($userHasSchool): School
    {
        $this->userHasSchool = $userHasSchool;
        return $this;
    }

    /**
     * @return MasterData[]|ArrayCollection
     */
    public function getMasterData()
    {
        return $this->masterData;
    }

    /**
     * @param MasterData[]|ArrayCollection $masterData
     * @return School
     */
    public function setMasterData($masterData): School
    {
        $this->masterData = $masterData;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getAuditEnd(): ?\DateTime
    {
        return $this->auditEnd;
    }

    /**
     * @param \DateTime|null $auditEnd
     */
    public function setAuditEnd(?\DateTime $auditEnd): void
    {
        $this->auditEnd = $auditEnd;
    }

    /**
     * @return User|null
     */
    public function getConsultant(): ?User
    {
        return $this->consultant;
    }

    /**
     * @param User|null $consultant
     */
    public function setConsultant(?User $consultant): void
    {
        $this->consultant = $consultant;
    }

    /**
     * @return string[]|null
     */
    public function getFlags(): array
    {
        if (\is_null($this->flags)) {
            $this->setFlags([]);
        }

        return $this->flags;
    }

    /**
     * @param string[] $flags
     */
    public function setFlags(array $flags): void
    {
        $this->flags = $flags;
    }

    public function isFlagEqual(string $flag, ?bool $value = true): bool
    {
        return $this->flags
            && \in_array($flag, $this->flags, true)
            && $this->flags[$flag] === $value;
    }

    public function isMiniCheck(): ?bool
    {
        return $this->miniCheck;
    }

    public function setMiniCheck(?bool $miniCheck): School
    {
        $this->miniCheck = $miniCheck;
        return $this;
    }

    public function getMiniCheckName(): ?string
    {
        return $this->miniCheckName;
    }

    public function setMiniCheckName(?string $miniCheckName): School
    {
        $this->miniCheckName = $miniCheckName;
        return $this;
    }

    public function getMiniCheckEmail(): ?string
    {
        return $this->miniCheckEmail;
    }

    public function setMiniCheckEmail(?string $miniCheckEmail): School
    {
        $this->miniCheckEmail = $miniCheckEmail;
        return $this;
    }


    /**
     * @return Collection|Result[]
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(Result $result): self
    {
        if (! $this->results->contains($result)) {
            $this->results[] = $result;
            $result->setSchool($this); // wichtig: bidirektional setzen
        }

        return $this;
    }

    public function removeResult(Result $result): self
    {
        if ($this->results->removeElement($result)) {
            // set the owning side to null (unless already changed)
            if ($result->getSchool() === $this) {
                $result->setSchool(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Survey[]
     */
    public function getSurveys(): Collection
    {
        return $this->surveys;
    }

    public function addSurvey(Survey $surveys): self
    {
        if (! $this->surveys->contains($surveys)) {
            $this->surveys[] = $surveys;
            $surveys->setSchool($this); // wichtig: bidirektional setzen
        }

        return $this;
    }

    public function removeSurvey(Survey $survey): self
    {
        if ($this->surveys->removeElement($survey)) {
            // set the owning side to null (unless already changed)
            if ($survey->getSchool() === $this) {
                $survey->setSchool(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FoodSurvey[]
     */
    public function getFoodSurveys(): Collection
    {
        return $this->foodSurveys;
    }

    public function addFoodSurvey(FoodSurvey $foodSurvey): self
    {
        if (! $this->foodSurveys->contains($foodSurvey)) {
            $this->foodSurveys[] = $foodSurvey;
            $foodSurvey->setSchool($this); // wichtig: bidirektional setzen
        }

        return $this;
    }

    public function removeFoodSurvey(FoodSurvey $foodSurvey): self
    {
        if ($this->foodSurveys->removeElement($foodSurvey)) {
            // set the owning side to null (unless already changed)
            if ($foodSurvey->getSchool() === $this) {
                $foodSurvey->setSchool(null);
            }
        }

        return $this;
    }

    /**
     * @return UserHasSchool[]|ArrayCollection
     */
    public function getConsultants(): ArrayCollection
    {
        if ($this->getUserHasSchool()) {
            return $this->getUserHasSchool()->filter(function (UserHasSchool $userHasSchool) {
                return $userHasSchool->getRole() === User::ROLE_CONSULTANT;
            });
        }
        return new ArrayCollection();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'miniCheck' => $this->isMiniCheck(),
            'miniCheckFull' => ! \is_null($this->getMiniCheckName()) || ! \is_null($this->getMiniCheckEmail()),
            'createdAt' => $this->getCreatedAt(),
            'address' => $this->getAddress() ? $this->getAddress()->jsonSerialize() : null,
            'audit_end' => $this->getAuditEnd(),
            'hasMasterData' => $this->getMasterData()->count() > 0,
            'resultsCount' => $this->getResults()->count(),
            'surveysCount' => $this->getSurveys()->count(),
        ];
    }

    /**
     * @return string|null
     */
    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
