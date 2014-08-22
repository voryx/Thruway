<?php

use Thruway\Message\ChallengeMessage;

class SimpleClientAuth implements \Thruway\ClientAuthenticationInterface {
    public function getAuthId()
    {
        // TODO: Implement getAuthId() method.
    }

    public function setAuthId($authid)
    {
        // TODO: Implement setAuthId() method.
    }

    public function getAuthMethods()
    {
        return array("simplysimple");
        // TODO: Implement getAuthMethods() method.
    }

    public function getAuthenticateFromChallenge(ChallengeMessage $msg)
    {
        return new \Thruway\Message\AuthenticateMessage("letMeIn");
    }

} 