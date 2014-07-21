<?php
if (file_exists(__DIR__.'/../../../../autoload.php')) {
    require __DIR__.'/../../../../autoload.php';
} else {
    require __DIR__ . '/../../vendor/autoload.php';
}

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