<?php

use Thruway\Message\ChallengeMessage;

/**
 * Class SimpleClientAuth
 */
class SimpleClientAuth implements \Thruway\ClientAuthenticationInterface
{

    public function getAuthId()
    {
        // TODO: Implement getAuthId() method.
    }

    /**
     * @param mixed $authid
     */
    public function setAuthId($authid)
    {
        // TODO: Implement setAuthId() method.
    }

    /**
     * @return array
     */
    public function getAuthMethods()
    {
        return ["simplysimple"];
        // TODO: Implement getAuthMethods() method.
    }

    /**
     * @param ChallengeMessage $msg
     * @return \Thruway\Message\AuthenticateMessage
     */
    public function getAuthenticateFromChallenge(ChallengeMessage $msg)
    {
        return new \Thruway\Message\AuthenticateMessage("letMeIn");
    }

} 