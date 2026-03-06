<?php

namespace App\Entity\Survey;

use App\Entity\School;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'survey_school_participation')]
#[ORM\Entity(repositoryClass: \App\Repository\Survey\SurveySchoolParticipationRepository::class)]
class SurveySchoolParticipation
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private $id;

    /**
     * @var Survey|null
     */
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Survey\Survey::class, inversedBy: 'schoolParticipations')]
    private $survey;

    /**
     * @var School|null
     */
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\School::class)]
    private $school;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $hasParticipated = false;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $participatedAt;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): self
    {
        $this->survey = $survey;
        return $this;
    }

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function setSchool(?School $school): self
    {
        $this->school = $school;
        return $this;
    }

    public function getHasParticipated(): bool
    {
        return $this->hasParticipated;
    }

    public function setHasParticipated(bool $hasParticipated): self
    {
        $this->hasParticipated = $hasParticipated;
        return $this;
    }

    public function getParticipatedAt(): ?\DateTime
    {
        return $this->participatedAt;
    }

    public function setParticipatedAt(?\DateTime $participatedAt): self
    {
        $this->participatedAt = $participatedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
