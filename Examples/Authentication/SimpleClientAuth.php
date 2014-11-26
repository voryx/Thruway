<?php

use Thruway\Message\ChallengeMessage;

/**
 * Class SimpleClientAuth
 */
class SimpleClientAuth implements \Thruway\Authentication\ClientAuthenticationInterface
{

    /**
     * Get authentication ID
     * 
     * @return mixed
     */
    public function getAuthId()
    {
        // TODO: Implement getAuthId() method.
    }

    /**
     * Set authentication
     * 
     * @param mixed $authid
     */
    public function setAuthId($authid)
    {
        // TODO: Implement setAuthId() method.
    }

    /**
     * Get list support authentication methods
     * 
     * @return array
     */
    public function getAuthMethods()
    {
        return ["simplysimple"];
        // TODO: Implement getAuthMethods() method.
    }

    /**
     * Make Authenticate message from challenge message
     * 
     * @param \Thruway\Message\ChallengeMessage $msg
     * @return \Thruway\Message\AuthenticateMessage
     */
    public function getAuthenticateFromChallenge(ChallengeMessage $msg)
    {
        return new \Thruway\Message\AuthenticateMessage("letMeIn");
    }

} 