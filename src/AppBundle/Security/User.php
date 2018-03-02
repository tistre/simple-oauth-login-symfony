<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Tistre\SimpleOAuthLogin\OAuthInfo;


class User implements UserInterface
{
    const DEFAULT_USERNAME = 'anonymous';

    /** @var OAuthInfo */
    protected $oAuthInfo;

    /** @var string */
    protected $mail = '';

    /** @var string */
    protected $name = '';

    /** @var array */
    protected $roles = [];


    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->setOAuthInfo(new OAuthInfo([]));
    }


    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }


    /**
     * @param array $roles
     * @return self
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;
        return $this;
    }


    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword(): string
    {
        return '';
    }


    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }


    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        $username = $this->getMail();

        if (strlen($username) === 0) {
            $username = self::DEFAULT_USERNAME;
        }

        return $username;
    }


    /**
     * @param string $username
     * @return self
     */
    public function setUsername(string $username): User
    {
        return $this->setMail($username);
    }


    /**
     * @return string
     */
    public function getMail(): string
    {
        return $this->oAuthInfo->getMail();
    }


    /**
     * @param string $mail
     * @return self
     */
    public function setMail(string $mail): User
    {
        $this->mail = $mail;
        $this->oAuthInfo->setMail($mail);

        return $this;
    }


    /**
     * @return string
     */
    public function getName()
    {
        if (strlen($this->name) > 0) {
            return $this->name;
        }

        return $this->getUsername();
    }


    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): User
    {
        $this->name = $name;
        $this->oAuthInfo->setName($name);

        return $this;
    }


    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }


    /**
     * @param OAuthInfo $oAuthInfo
     * @return self
     */
    public function setOAuthInfo(OAuthInfo $oAuthInfo): User
    {
        $this->oAuthInfo = $oAuthInfo;
        return $this;
    }


    /**
     * @return OAuthInfo
     */
    public function getOAuthInfo(): OAuthInfo
    {
        return $this->oAuthInfo;
    }
}