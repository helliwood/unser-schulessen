<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 06.06.19
 * Time: 10:10
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * MasterData Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Table(uniqueConstraints={
 *      @ORM\UniqueConstraint(name="MD_School_Version_unique",
 *      columns={"school_id", "school_year"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\MasterDataRepository")
 */
class MasterData
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
     * @var SchoolYear|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\SchoolYear", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, name="school_year", referencedColumnName="year", onDelete="RESTRICT")
     */
    protected $schoolYear;

    /**
     *
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $finalised = false;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected $finalisedBy;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $finalisedAt;
    /**
     * @var MasterDataEntry[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\App\Entity\MasterDataEntry", cascade={"persist"}, mappedBy="masterData", fetch="EAGER")
     */
    protected $entries;

    /**
     * MasterData constructor.
     */
    public function __construct()
    {
        $this->entries = new ArrayCollection();
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
     * @return MasterData
     */
    public function setId(?int $id): MasterData
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
     * @return MasterData
     */
    public function setSchool(?School $school): MasterData
    {
        $this->school = $school;
        return $this;
    }

    /**
     * @return SchoolYear|null
     */
    public function getSchoolYear(): ?SchoolYear
    {
        return $this->schoolYear;
    }

    /**
     * @param SchoolYear|null $schoolYear
     * @return MasterData
     */
    public function setSchoolYear(?SchoolYear $schoolYear): MasterData
    {
        $this->schoolYear = $schoolYear;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getFinalised(): ?bool
    {
        return $this->finalised;
    }

    /**
     * @param bool|null $finalised
     * @return MasterData
     */
    public function setFinalised(?bool $finalised): MasterData
    {
        $this->finalised = $finalised;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getFinalisedBy(): ?User
    {
        return $this->finalisedBy;
    }

    /**
     * @param User|null $finalisedBy
     * @return MasterData
     */
    public function setFinalisedBy(?User $finalisedBy): MasterData
    {
        $this->finalisedBy = $finalisedBy;
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
     * @return MasterData
     */
    public function setCreatedAt(?\DateTime $createdAt): MasterData
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getFinalisedAt(): ?\DateTime
    {
        return $this->finalisedAt;
    }

    /**
     * @param \DateTime|null $finalisedAt
     * @return MasterData
     */
    public function setFinalisedAt(?\DateTime $finalisedAt): MasterData
    {
        $this->finalisedAt = $finalisedAt;
        return $this;
    }

    /**
     * @param string|null $step
     * @return MasterDataEntry[]|ArrayCollection
     */
    public function getEntries(?string $step = null)
    {
        if ($step) {
            return $this->entries->filter(static function (MasterDataEntry $entry) use ($step) {
                return $entry->getStep() === $step;
            });
        }
        return $this->entries;
    }

    /**
     * @param MasterDataEntry[]|ArrayCollection $entries
     * @return MasterData
     */
    public function setEntries($entries): MasterData
    {
        $this->entries = $entries;
        return $this;
    }
}
