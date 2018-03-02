<?php

namespace AppBundle\Security;


class SimpleOAuthConfig
{
    /** @var array */
    protected $oAuthConfigs;

    /** @var array */
    protected $fakeOAuth;

    /** @var array */
    protected $userDetails;


    /**
     * SimpleOAuthConfig constructor.
     * @param $simpleOAuthParams
     */
    public function __construct($simpleOAuthParams)
    {
        $this->setArray($simpleOAuthParams);
    }


    /**
     * See parameters.yml.dist for parameter format
     *
     * @param mixed $params
     * @return self
     */
    protected function setArray($params)
    {
        if (!is_array($params)) {
            $params = [];
        }

        foreach (['oauth_configs', 'fake_oauth', 'user_details'] as $key) {
            if (!(isset($params[$key]) && is_array($params[$key]))) {
                $params[$key] = [];
            }
        }

        $this->oAuthConfigs = $params['oauth_configs'];
        $this->fakeOAuth = $params['fake_oauth'];
        $this->userDetails = $params['user_details'];

        return $this;
    }


    /**
     * @return array
     */
    public function getOAuthConfigs()
    {
        return $this->oAuthConfigs;
    }


    /**
     * @return array
     */
    public function getFakeOAuth()
    {
        return $this->fakeOAuth;
    }


    /**
     * @return array
     */
    public function getUserDetails()
    {
        return $this->userDetails;
    }


    /**
     * @param $username
     * @return User
     */
    public function getUserByUsername($username): User
    {
        $user = new User();

        if (!isset($this->userDetails[$username])) {
            return $user;
        }

        $user->setUsername($username);

        $details = $this->userDetails[$username];

        if (!empty($details['name'])) {
            $user->setName($details['name']);
        }

        if (isset($details['roles']) && is_array($details['roles'])) {
            $user->setRoles($details['roles']);
        }
        
        return $user;
    }
}
