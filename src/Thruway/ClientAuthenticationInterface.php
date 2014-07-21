<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 7/20/14
 * Time: 2:34 PM
 */

namespace Thruway;


use Thruway\Message\ChallengeMessage;

interface ClientAuthenticationInterface {
    public function getAuthId();
    public function setAuthId($authid);

    public function getAuthMethods();

    public function getAuthenticateFromChallenge(ChallengeMessage $msg);
}