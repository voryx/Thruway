<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 7/19/14
 * Time: 7:54 PM
 */

namespace Thruway;

use Thruway\Message\Message;

interface AuthenticationManagerInterface {
    public function onAuthenticationMessage(Realm $realm, Session $session, Message $msg);
} 