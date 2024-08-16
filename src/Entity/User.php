<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 */

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements EquatableInterface, UserInterface, \KevinPapst\TablerBundle\Model\UserInterface
{

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    #[ORM\Column(type: 'string')]
    protected string $firstname;
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $lastname;
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $email;

    #[ORM\Column(name: 'username', type: 'string', length: 255, unique: true, nullable: true)]
    protected ?string $username = null;

    #[ORM\Column(name: 'avatar', type: 'string', length: 2048, nullable: true)]
    protected ?string $avatar;

    #[ORM\JoinColumn(name: 'accessgroup_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: AccessGroup::class)]
    private AccessGroup $access_group;

    /**
     * @see \Serializable::serialize()
     */
    public function serialize(): string
    {
        return serialize( [
            $this->id,
            $this->username
        ] );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized): void
    {
        list (
            $this->id,
            $this->username
            ) = unserialize($serialized);
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if ($this->getId() === $user->getId()) {
            return true;
        }

        return false;
    }

    public function __toString()
    {
        return $this->firstname . " " . $this->lastname;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email

     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function getMemberSince(): DateTime
    {
        return new DateTime();
    }

    public function getTitle(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->getFirstname() . " " . $this->getLastname();
    }


    public function getRoles(): array
    {
        $roles = [];
        if ($this->access_group != null)
        {
            $roles[] = 'ROLE_USER';
        }
        if ($this->email == "timwhite88@gmail.com")
        {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return User
     */
    public function setAvatar(string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }


    /**
     * Get avatar
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Set access_group
     */
    public function setAccessGroup(AccessGroup $accessGroup = null): static
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group
     */
    public function getAccessGroup(): AccessGroup
    {
        return $this->access_group;
    }

    public function eraseCredentials()
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
