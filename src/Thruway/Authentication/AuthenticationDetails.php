<?php

namespace Thruway\Authentication;

/**
 * Class AuthenticationDetails
 *
 * @package Thruway\Authentication
 */

class AuthenticationDetails
{

    /**
     * @var int
     */
    private $authId;
    /**
     * @var mixed
     */
    private $authMethod;
    /**
     * @var mixed
     */
    private $challenge;
    /**
     * @var mixed
     */
    private $challengeDetails;
    /**
     * @var array
     */
    private $authRoles;

    function __construct()
    {
        $this->authRoles = [];
    }

    /**
     * Set challenge details
     *
     * @param mixed $challengeDetails
     */
    public function setChallengeDetails($challengeDetails)
    {
        $this->challengeDetails = $challengeDetails;
    }

    /**
     * Get challenge details
     *
     * @return mixed
     */
    public function getChallengeDetails()
    {
        return $this->challengeDetails;
    }

    /**
     * Set challenge info
     *
     * @param mixed $challenge
     */
    public function setChallenge($challenge)
    {
        $this->challenge = $challenge;
    }

    /**
     * Get challenge data
     *
     * @return mixed
     */
    public function getChallenge()
    {
        return $this->challenge;
    }

    /**
     * Set authentication ID
     *
     * @param mixed $authId
     */
    public function setAuthId($authId)
    {
        $this->authId = $authId;
    }

    /**
     * Get authentication ID
     *
     * @return mixed
     */
    public function getAuthId()
    {
        return $this->authId;
    }

    /**
     * Set authentication method
     *
     * @param mixed $authMethod
     */
    public function setAuthMethod($authMethod)
    {
        $this->authMethod = $authMethod;
    }

    /**
     * Get authentication method
     *
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    /**
     * Create anonymous
     *
     * @return \Thruway\Authentication\AuthenticationDetails
     */
    static public function createAnonymous()
    {
        $authDetails = new AuthenticationDetails();
        $authDetails->setAuthId("anonymous");
        $authDetails->setAuthMethod("anonymous");

        return $authDetails;
    }

    /**
     * @return array
     */
    public function getAuthRoles()
    {
        return $this->authRoles;
    }

    /**
     * @param array $authRoles
     */
    public function setAuthRoles($authRoles)
    {
        $this->authRoles = $authRoles;
    }

    /**
     * @param $authRole
     */
    public function addAuthRole($authRole) {
        if (is_array($authRole)) {
            $this->authRoles = array_merge($authRole, $this->authRoles);
        } else {
            // this is done this way so that most recent addition will be the
            // singular role for compatibility
            array_unshift($this->authRoles, $authRole);
        }
    }

    /**
     * @param $authRole
     * @return bool
     */
    public function hasAuthRole($authRole) {
        if (in_array($authRole, $this->authRoles)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getAuthRole() {
        if (count($this->authRoles) > 0) {
            return $this->authRoles[0];
        } else {
            return FALSE;
        }
    }
}