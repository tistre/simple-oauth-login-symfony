<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Tistre\SimpleOAuthLogin\OAuthInfo;


class User implements UserInterface
{
    const DEFAULT_USERNAME = 'anonymous';

    /** @var OAuthInfo */
    protected $oAuthInfo;

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
    public function setRoles(array $roles)
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
    public function getPassword()
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
    public function getUsername()
    {
        if (strlen($this->oAuthInfo->getMail()) > 0) {
            return $this->oAuthInfo->getMail();
        }

        return self::DEFAULT_USERNAME;
    }


    /**
     * @return string
     */
    public function getMail()
    {
        return $this->oAuthInfo->getMail();
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->oAuthInfo->getName();
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
    public function setOAuthInfo(OAuthInfo $oAuthInfo)
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