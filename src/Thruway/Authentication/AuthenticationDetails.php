<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 7/19/14
 * Time: 8:49 PM
 */

namespace Thruway\Authentication;


class AuthenticationDetails {
    private $authId;
    private $authMethod;
    private $challenge;

    /**
     * @param mixed $challenge
     */
    public function setChallenge($challenge)
    {
        $this->challenge = $challenge;
    }

    /**
     * @return mixed
     */
    public function getChallenge()
    {
        return $this->challenge;
    }

    /**
     * @param mixed $authId
     */
    public function setAuthId($authId)
    {
        $this->authId = $authId;
    }

    /**
     * @return mixed
     */
    public function getAuthId()
    {
        return $this->authId;
    }

    /**
     * @param mixed $authMethod
     */
    public function setAuthMethod($authMethod)
    {
        $this->authMethod = $authMethod;
    }

    /**
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }
}