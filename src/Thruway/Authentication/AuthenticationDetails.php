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
    private $challengeDetails;

    /**
     * @param mixed $challengeDetails
     */
    public function setChallengeDetails($challengeDetails)
    {
        $this->challengeDetails = $challengeDetails;
    }

    /**
     * @return mixed
     */
    public function getChallengeDetails()
    {
        return $this->challengeDetails;
    }

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

    static public function createAnonymous() {
        $authDetails = new AuthenticationDetails();
        $authDetails->setAuthId("anonymous");
        $authDetails->setAuthMethod("anonymous");

        return $authDetails;
    }
}