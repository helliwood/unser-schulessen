<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-27
 * Time: 08:30
 */

namespace App\Entity;

use DateTime;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("email")
 * @IgnoreAnnotation("phpcsSuppress")
 */
class User extends AbstractEntity implements UserInterface, \Serializable, \JsonSerializable
{
    public const STATE_NOT_ACTIVATED = 0;
    public const STATE_ACTIVE = 1;
    public const STATE_BLOCKED = 2;

    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_CONSULTANT = 'ROLE_CONSULTANT';
    public const ROLE_HEADMASTER = 'ROLE_HEADMASTER';
    public const ROLE_FOOD_COMMISSIONER = 'ROLE_FOOD_COMMISSIONER';
    public const ROLE_MENSA_AG = 'ROLE_MENSA_AG';
    public const ROLE_SCHOOL_AUTHORITIES = 'ROLE_SCHOOL_AUTHORITIES';
    public const ROLE_SCHOOL_AUTHORITIES_ACTIVE = 'ROLE_SCHOOL_AUTHORITIES_ACTIVE';
    public const ROLE_KITCHEN = 'ROLE_KITCHEN';
    public const ROLE_GUEST = 'ROLE_GUEST';

    /*
     * Diese Bundesländer haben Sonderregeln wegen den Ernährungsberatern!
     */
    public const STATES_WITH_CONSULTANTS = ['by', 'he', 'rp'];

    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string|null
     * @Assert\Email()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=190, unique=true)
     */
    private $email;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var Person|null
     *
     * @ORM\OneToOne(targetEntity="\App\Entity\Person", cascade={"persist"}, fetch="EAGER", inversedBy="user")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    private $person;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false, options={"default" : 0})
     */
    protected $state = self::STATE_NOT_ACTIVATED;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @var string[]|null
     */
    private $roles = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime|null
     */
    private $currentLogin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime|null
     */
    private $lastLogin;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=32, nullable=true, unique=true)
     */
    protected $resetPasswordHash;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $hashExpirationDate;

    /**
     * @var bool
     */
    private $sendActivationMail = false;

    /**
     * @var UserHasSchool[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="UserHasSchool", mappedBy="user")
     */
    private $userHasSchool;

    /**
     * @var School|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\School", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $currentSchool;

    /**
     * @var bool
     */
    private $currentSchoolSecurityChecked = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $employee = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $tempPassword = false;

    /**
     * @var string|null
     */
    private $newPassword;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->userHasSchool = new ArrayCollection();
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
     * @return User
     */
    public function setId(?int $id): User
    {
        $this->id = $id;
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
     * @return User
     */
    public function setEmail(?string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return User
     */
    public function setPassword(?string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return Person|null
     */
    public function getPerson(): ?Person
    {
        return $this->person;
    }

    /**
     * @param Person|null $person
     * @return User
     */
    public function setPerson(?Person $person): User
    {
        $this->person = $person;
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
     * @return User
     */
    public function setState(int $state): User
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
     * @return User
     */
    public function setCreatedAt(?DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @param string $role
     * @return User
     */
    public function addRole(string $role): User
    {
        $this->roles[] = $role;
        $this->roles = \array_unique($this->roles);
        return $this;
    }

    /**
     * @param string $role
     * @return User
     */
    public function removeRole(string $role): User
    {
        $key = \array_search($role, $this->roles);
        if ($key !== false) {
            unset($this->roles[$key]);
        }
        return $this;
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    public function getRoles(): ?array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        $schoolRole = $this->getRoleByCurrentSchool();
        if ($schoolRole) {
            $roles[] = $schoolRole;
        }

        return \array_unique($roles);
    }

    /**
     * @param string[] $roles
     * @return User
     */
    public function setRoles(?array $roles): User
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCurrentLogin(): ?DateTime
    {
        return $this->currentLogin;
    }

    /**
     * @param DateTime|null $currentLogin
     * @return User
     */
    public function setCurrentLogin(?DateTime $currentLogin): User
    {
        $this->currentLogin = $currentLogin;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param DateTime|null $lastLogin
     * @return User
     */
    public function setLastLogin(?DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSendActivationMail(): bool
    {
        return $this->sendActivationMail;
    }

    /**
     * @param bool $sendActivationMail
     * @return User
     */
    public function setSendActivationMail(bool $sendActivationMail): User
    {
        $this->sendActivationMail = $sendActivationMail;
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
     * @return User
     */
    public function setUserHasSchool($userHasSchool): User
    {
        $this->userHasSchool = $userHasSchool;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmployee(): bool
    {
        return $this->employee;
    }

    /**
     * @param bool $employee
     * @return User
     */
    public function setEmployee(bool $employee): User
    {
        $this->employee = $employee;
        return $this;
    }

    /**
     * @return School|null
     * @throws \Exception
     */
    public function getCurrentSchool(): ?School
    {
        // For Rheinland-Pfalz and Hessen, we also allow the state CONSULTANT
        // to be used for the current school selection.
        if (! \in_array($this->getStateCountry(), self::STATES_WITH_CONSULTANTS)) {
            if (\is_null($this->currentSchool) && \count($this->getUserHasSchool()) > 0) {
                foreach ($this->getUserHasSchool() as $userHasSchool) {
                    if ($userHasSchool->getState() === UserHasSchool::STATE_ACCEPTED) {
                        $this->setCurrentSchool($userHasSchool->getSchool());
                        return $this->currentSchool;
                    }
                }
            } elseif (! \is_null($this->currentSchool) && ! $this->currentSchoolSecurityChecked) {
                foreach ($this->getUserHasSchool() as $userHasSchool) {
                    if ($userHasSchool->getState() === UserHasSchool::STATE_ACCEPTED &&
                        $this->currentSchool === $userHasSchool->getSchool()) {
                        $this->currentSchoolSecurityChecked = true;
                        return $this->currentSchool;
                    }
                }
                return null;
            }
            return $this->currentSchool;
        } elseif (\in_array($this->getStateCountry(), self::STATES_WITH_CONSULTANTS)) {
            if (\is_null($this->currentSchool) && \count($this->getUserHasSchool()) > 0) {
                foreach ($this->getUserHasSchool() as $userHasSchool) {
                    if (\in_array($userHasSchool->getState(), [UserHasSchool::STATE_ACCEPTED, UserHasSchool::STATE_CONSULTANT])) {
                        $this->setCurrentSchool($userHasSchool->getSchool());
                        return $this->currentSchool;
                    }
                }
            } elseif (! \is_null($this->currentSchool) && ! $this->currentSchoolSecurityChecked) {
                foreach ($this->getUserHasSchool() as $userHasSchool) {
                    if (\in_array($userHasSchool->getState(), [UserHasSchool::STATE_ACCEPTED, UserHasSchool::STATE_CONSULTANT])
                        && $this->currentSchool === $userHasSchool->getSchool()) {
                        $this->currentSchoolSecurityChecked = true;
                        return $this->currentSchool;
                    }
                }
                return null;
            }
            return $this->currentSchool;
        }
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function getRoleByCurrentSchool(): ?string
    {
        if (! \is_null($this->getCurrentSchool())) {
            foreach ($this->getUserHasSchool() as $userHasSchool) {
                if ($userHasSchool->getSchool() === $this->getCurrentSchool()) {
                    return $userHasSchool->getRole();
                }
            }
        }
        return null;
    }

    /**
     * @param School|null $currentSchool
     * @return User
     * @throws \Exception
     */
    public function setCurrentSchool(?School $currentSchool): User
    {
        if (! $this->hasSchool($currentSchool)) {
            throw new \Exception('User doesn\'t has this school.');
        }
        if (! $this->isAccepted($currentSchool)) {
            throw new \Exception('School not accepted!');
        }
        $this->currentSchool = $currentSchool;
        return $this;
    }

    /**
     * @param School $school
     * @return bool
     */
    public function hasSchool(School $school): bool
    {
        foreach ($this->getUserHasSchool() as $userHasSchool) {
            if ($userHasSchool->getSchool() === $school) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param School $school
     * @return bool
     */
    public function isAccepted(School $school): bool
    {
        foreach ($this->getUserHasSchool() as $userHasSchool) {
            if (! \in_array($this->getStateCountry(), self::STATES_WITH_CONSULTANTS)
                && $userHasSchool->getSchool() === $school
                && $userHasSchool->getState() === UserHasSchool::STATE_ACCEPTED
            ) {
                return true;
            } elseif (\in_array($this->getStateCountry(), self::STATES_WITH_CONSULTANTS)
                && $userHasSchool->getSchool() === $school
                && \in_array($userHasSchool->getState(), [UserHasSchool::STATE_ACCEPTED, UserHasSchool::STATE_CONSULTANT])
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string|null
     */
    public function getResetPasswordHash(): ?string
    {
        return $this->resetPasswordHash;
    }

    /**
     * @param string|null $resetPasswordHash
     * @return User
     */
    public function setResetPasswordHash(?string $resetPasswordHash): User
    {
        $this->resetPasswordHash = $resetPasswordHash;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getHashExpirationDate(): ?DateTime
    {
        return $this->hashExpirationDate;
    }

    /**
     * @param DateTime|null $hashExpireDate
     * @return User
     */
    public function setHashExpirationDate(?DateTime $hashExpireDate): User
    {
        $this->hashExpirationDate = $hashExpireDate;
        return $this;
    }

    /**
     * String representation of object
     * @link  https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize(): string
    {
        return \serialize([
            $this->id,
            $this->email,
            $this->password,
            $this->state
        ]);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->email,
            $this->password,
            $this->state
        ] = \unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt(): ?string
    {
        // TODO: Implement getSalt() method.
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->getEmail();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @return bool
     */
    public function isTempPassword(): bool
    {
        return $this->tempPassword;
    }

    /**
     * @param bool $tempPassword
     * @return User
     */
    public function setTempPassword(bool $tempPassword): User
    {
        $this->tempPassword = $tempPassword;
        return $this;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'displayName' => $this->getDisplayName(),
            'state' => $this->state
        ];
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->person && "" !== \trim($this->person->getDisplayName())
            ? $this->person->getDisplayName()
            : $this->email;
    }


    /**
     * @return string|null
     */
    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    /**
     * @param string|null $newPassword
     */
    public function setNewPassword(?string $newPassword): void
    {
        $this->newPassword = $newPassword;
    }
}
