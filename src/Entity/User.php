<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUser;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use KevinPapst\AdminLTEBundle\Model\UserInterface as ThemeUser;

/**
 * Class User
 * @ORM\Entity
 */

class User extends OAuthUser implements EquatableInterface,ThemeUser
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $firstname;
    /**
     * @ORM\Column(type="string", nullable=TRUE)
     */
    protected $lastname;
    /**
     * @ORM\Column(type="string", nullable=TRUE)
     */
    protected $email;

    /**
     * @ORM\Column(name="username", type="string", length=255, unique=true, nullable=TRUE)
     */
    protected $username = null;

    /**
     * @ORM\Column(name="avatar", type="string", length=255, nullable=TRUE)
     */
    protected $avatar;

    /**
     * @ORM\ManyToOne(targetEntity="AccessGroup")
     * @ORM\JoinColumn(name="accessgroup_id", referencedColumnName="id", nullable=FAlSE)
     */
    private $access_group;

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize( [
            $this->id,
            $this->username
        ] );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username
            ) = unserialize($serialized);
    }

    public function isEqualTo(UserInterface $user)
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
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function getIdentifier()
    {
        return $this->getUsername();
    }

    public function isOnline()
    {
        return true;
    }

    public function getMemberSince()
    {
        return new DateTime();
    }

    public function getTitle()
    {
        return '';
    }

    public function getName()
    {
        return $this->getFirstname() . " " . $this->getLastname();
    }


    public function getRoles()
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
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }


    /**
     * Get avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set access_group
     *
     * @param AccessGroup $accessGroup
     *
     * @return User
     */
    public function setAccessGroup(AccessGroup $accessGroup = null)
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group
     *
     * @return AccessGroup
     */
    public function getAccessGroup()
    {
        return $this->access_group;
    }
}
