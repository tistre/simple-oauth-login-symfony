<?php

namespace AppBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Tistre\SimpleOAuthLogin\OAuthInfo;


class SessionUserProvider implements UserProviderInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var SimpleOAuthConfig */
    protected $simpleOAuthConfig;


    /**
     * SessionUserProvider constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, SimpleOAuthConfig $simpleOAuthConfig)
    {
        $this->logger = $logger;
        $this->simpleOAuthConfig = $simpleOAuthConfig;
    }


    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        return $this->simpleOAuthConfig->getUserByUsername($username);
    }


    /**
     * Refreshes the user.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the user is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException('Instances of "%s" are not supported.', get_class($user));
        }

        // Don't always reload, just copy the data
        // TODO: Could we simply return the original $user object?
        // TODO: Or at least implement a clone method?
        
        return (new User())
            ->setUsername($user->getUsername())
            ->setName($user->getName())
            ->setRoles($user->getRoles());
    }


    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return ($class === User::class);
    }
}
