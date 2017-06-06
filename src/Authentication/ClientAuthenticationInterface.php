<?php

namespace Thruway\Authentication;

use Thruway\Message\ChallengeMessage;

/**
 * interface of ClientAuthentication
 */
interface ClientAuthenticationInterface
{

    /**
     * Get AuthID
     *
     * @return mixed
     */
    public function getAuthId();

    /**
     * Set AuthID
     *
     * @param mixed $authid
     */
    public function setAuthId($authid);

    /**
     * Get list supported authentication method
     *
     * @return array
     */
    public function getAuthMethods();

    /**
     * Get authentication message from challenge message
     *
     * @param \Thruway\Message\ChallengeMessage $msg
     * @return \Thruway\Message\AuthenticateMessage|boolean
     */
    public function getAuthenticateFromChallenge(ChallengeMessage $msg);

}
