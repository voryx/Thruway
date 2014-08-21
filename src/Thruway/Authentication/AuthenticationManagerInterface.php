<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 7/19/14
 * Time: 7:54 PM
 */

namespace Thruway\Authentication;

use Thruway\Message\Message;
use Thruway\Realm;
use Thruway\Session;

interface AuthenticationManagerInterface {
    public function onAuthenticationMessage(Realm $realm, Session $session, Message $msg);
    public function onSessionClose(Session $session);
} 