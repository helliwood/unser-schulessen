<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 28.03.19
 * Time: 13:22
 */

namespace App\Entity;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Person Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\PersonRepository")
 * @IgnoreAnnotation("phpcsSuppress")
 */
class Person implements \JsonSerializable
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
     *
     * @var string|null
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $salutation;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    protected $academicTitle;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $firstName;

    /**
     *
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $lastName;

    /**
     *
     * @var string|null
     * @Assert\Email()
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected $email;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $telephone;

    /**
     *
     * @var string|null
     * @Assert\Length(max="255")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $note;

    /**
     * @var PersonType|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\PersonType", cascade={"persist"})
     * @ORM\JoinColumn(name="person_type", referencedColumnName="name", nullable=true, onDelete="RESTRICT")
     */
    protected $personType;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", inversedBy="persons", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $school;

    /**
     * @var User|null
     *
     * @Assert\Valid()
     * @ORM\OneToOne(targetEntity="\App\Entity\User", mappedBy="person", cascade={"persist"})
     */
    private $user;

    /**
     * Person constructor.
     */
    public function __construct()
    {
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
     * @return Person
     */
    public function setId(?int $id): Person
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    /**
     * @param string $salutation
     * @return Person
     */
    public function setSalutation(?string $salutation): Person
    {
        $this->salutation = $salutation;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAcademicTitle(): ?string
    {
        return $this->academicTitle;
    }

    /**
     * @param string|null $academicTitle
     * @return Person
     */
    public function setAcademicTitle(?string $academicTitle): Person
    {
        $this->academicTitle = $academicTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return Person
     */
    public function setFirstName(?string $firstName): Person
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return Person
     */
    public function setLastName(?string $lastName): Person
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return Person
     */
    public function setEmail(?string $email): Person
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    /**
     * @param string|null $telephone
     * @return Person
     */
    public function setTelephone(?string $telephone): Person
    {
        $this->telephone = $telephone;
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
     * @return Person
     */
    public function setNote(?string $note): Person
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return PersonType|null
     */
    public function getPersonType(): ?PersonType
    {
        return $this->personType;
    }

    /**
     * @param PersonType|null $personType
     * @return Person
     */
    public function setPersonType(?PersonType $personType): Person
    {
        $this->personType = $personType;
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
     * @return Person
     */
    public function setSchool(?School $school): Person
    {
        $this->school = $school;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return Person
     */
    public function setUser(?User $user): Person
    {
        $user->setPerson($this);
        $this->user = $user;
        return $this;
    }

    /**
     * @param bool $withSalutation
     * @return string
     */
    public function getDisplayName(bool $withSalutation = true): string
    {
        return ($this->getSalutation() && $this->getSalutation() !== "" &&
            $withSalutation && $this->getSalutation() !== 'Organisation' ? $this->getSalutation() . ' ' : '') .
            ($this->getAcademicTitle() && $this->getAcademicTitle() !== "" ? $this->getAcademicTitle() . ' ' : '') .
            \trim(($this->getFirstName() && $this->getFirstName() !== "" ? $this->getFirstName() . ' ' : '') .
                $this->getLastName());
    }


    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'salutation' => $this->getSalutation(),
            'academicTitle' => $this->getAcademicTitle(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'displayName' => $this->getDisplayName(),
            'personType' => $this->getPersonType() ? $this->getPersonType()->getName() : null,
            'email' => $this->getEmail(),
            'telephone' => $this->getTelephone(),
            'note' => $this->getNote(),
            'hasUser' => ! \is_null($this->getUser()) ?? false
        ];
    }
}
