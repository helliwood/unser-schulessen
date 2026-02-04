<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-29
 * Time: 09:30
 */

namespace App\Entity;

use DateTime;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserHasSchool Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserHasSchoolRepository")
 * @UniqueEntity(fields={"user","school"}, message="E-Mail nur 1x pro Schule.")
 * @IgnoreAnnotation("phpcsSuppress")
 */
class UserHasSchool implements \JsonSerializable
{
    public const STATE_REQUESTED = 0;
    public const STATE_ACCEPTED = 1;
    public const STATE_REJECTED = 2;
    public const STATE_BLOCKED = 3;
    public const STATE_CONSULTANT = 4;

    public const LABELS = [
        self::STATE_REQUESTED => 'Angefragt',
        self::STATE_ACCEPTED => 'Akzeptiert',
        self::STATE_REJECTED => 'Abgelehnt',
        self::STATE_BLOCKED => 'Gesperrt',
        self::STATE_CONSULTANT => 'Ernährungsberater',
    ];
    public const LABELS_HE = [
        self::STATE_REQUESTED => 'Angefragt',
        self::STATE_ACCEPTED => 'Akzeptiert',
        self::STATE_REJECTED => 'Abgelehnt',
        self::STATE_BLOCKED => 'Gesperrt',
        self::STATE_CONSULTANT => 'Berater VNS/ CleZi',
    ];

    public const ROLES = [
        'Gast' => User::ROLE_GUEST,
        'Küche/Verpflegungsanbieter' => User::ROLE_KITCHEN,
        'Schulleitung' => User::ROLE_HEADMASTER,
        'Schulträger' => User::ROLE_SCHOOL_AUTHORITIES,
        'Schulträger - aktiv' => User::ROLE_SCHOOL_AUTHORITIES_ACTIVE,
        'Verpflegungsausschuss' => User::ROLE_MENSA_AG,
        'Verpflegungsbeauftragte(r)' => User::ROLE_FOOD_COMMISSIONER,
    ];

    public const ROLES_SL = [
        'Gast' => User::ROLE_GUEST,
        'Küche/Verpflegungsanbieter' => User::ROLE_KITCHEN,
        'Schulleitung/Leitung FGTS' => User::ROLE_HEADMASTER,
        'Schulträger/Träger FGTS' => User::ROLE_SCHOOL_AUTHORITIES,
        'Schulträger/Träger FGTS - aktiv' => User::ROLE_SCHOOL_AUTHORITIES_ACTIVE,
        'Verpflegungsausschuss' => User::ROLE_MENSA_AG,
        'Verpflegungsbeauftragte(r)' => User::ROLE_FOOD_COMMISSIONER,
    ];

    public const ROLES_ADMIN_AREA = [
        'Administrator' => User::ROLE_ADMIN,
        'Ernährungsberater' => User::ROLE_CONSULTANT
    ];

    public const ROLE_LABELS = [
        User::ROLE_CONSULTANT => 'Ernährungsberater',
        User::ROLE_GUEST => 'Gast',
        User::ROLE_KITCHEN => 'Küche/Verpflegungsanbieter',
        User::ROLE_HEADMASTER => 'Schulleitung',
        User::ROLE_SCHOOL_AUTHORITIES => 'Schulträger',
        User::ROLE_SCHOOL_AUTHORITIES_ACTIVE => 'Schulträger - aktiv',
        User::ROLE_MENSA_AG => 'Verpflegungsausschuss',
        User::ROLE_FOOD_COMMISSIONER => 'Verpflegungsbeauftragte(r)',
    ];

    public const ROLE_LABELS_HE = [
        User::ROLE_CONSULTANT => 'Berater VNS/ CleZi',
        User::ROLE_GUEST => 'Gast',
        User::ROLE_KITCHEN => 'Küche/Verpflegungsanbieter',
        User::ROLE_HEADMASTER => 'Schulleitung',
        User::ROLE_SCHOOL_AUTHORITIES => 'Schulträger',
        User::ROLE_SCHOOL_AUTHORITIES_ACTIVE => 'Schulträger - aktiv',
        User::ROLE_MENSA_AG => 'Verpflegungsausschuss',
        User::ROLE_FOOD_COMMISSIONER => 'Verpflegungsbeauftragte(r)',
    ];

    /**
     * UserHasSchool constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    /**
     * @var User|null
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="\App\Entity\User", cascade={"persist"}, fetch="EAGER", inversedBy="userHasSchool")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var School|null
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", cascade={"persist"}, fetch="EAGER", inversedBy="userHasSchool")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $school;

    /**
     * @var PersonType|null
     *
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="PersonType", cascade={"persist"})
     * @ORM\JoinColumn(name="person_type", referencedColumnName="name", nullable=false, onDelete="RESTRICT")
     */
    private $personType;

    /**
     * @var string|null
     *
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=190, nullable=false)
     */
    private $role;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false, options={"default" : 0})
     */
    private $state = self::STATE_REQUESTED;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $respondedAt;

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return UserHasSchool
     */
    public function setUser(?User $user): UserHasSchool
    {
        $this->user = $user;
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
     * @return UserHasSchool
     */
    public function setSchool(?School $school): UserHasSchool
    {
        $this->school = $school;
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
     * @return UserHasSchool
     */
    public function setPersonType(?PersonType $personType): UserHasSchool
    {
        $this->personType = $personType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string|null $role
     * @return UserHasSchool
     */
    public function setRole(?string $role): UserHasSchool
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     * @return UserHasSchool
     */
    public function setState(int $state): UserHasSchool
    {
        $this->state = $state;
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
     * @return UserHasSchool
     */
    public function setCreatedAt(?DateTime $createdAt): UserHasSchool
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getRespondedAt(): ?DateTime
    {
        return $this->respondedAt;
    }

    /**
     * @param DateTime|null $respondedAt
     * @return UserHasSchool
     */
    public function setRespondedAt(?DateTime $respondedAt): UserHasSchool
    {
        $this->respondedAt = $respondedAt;
        return $this;
    }

    public function getLabels(string $state): string
    {
        if ($_ENV['APP_STATE_COUNTRY'] === 'he') {
            return self::LABELS_HE[$state];
        }
        return self::LABELS[$state];
    }

    public function getRoleLabels(): string
    {
        if ($_ENV['APP_STATE_COUNTRY'] === 'he') {
            return self::ROLE_LABELS_HE[$this->role];
        }
        return self::ROLE_LABELS[$this->role];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
        return [
            'user' => $this->getUser() ? $this->getUser()->jsonSerialize() : null,
            'school' => $this->getSchool() ? $this->getSchool()->jsonSerialize() : null,
            'personType' => $this->getPersonType() ? $this->getPersonType()->getName() : null,
            'state' => $this->getState(),
            'stateText' => $this->getLabels($this->getState()),
            'role' => $this->getRole(),
            'roleText' => $this->getRoleLabels(),//self::ROLE_LABELS[$this->getRole()],
            'createdAt' => $this->getCreatedAt(),
            'respondedAt' => $this->getRespondedAt()
        ];
    }
}
