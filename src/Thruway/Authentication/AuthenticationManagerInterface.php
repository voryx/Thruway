<?php

namespace Thruway\Authentication;

use Thruway\Message\Message;
use Thruway\Realm;
use Thruway\Session;

/**
 * Interface for authentication manager
 * 
 * @package Thruway\Authentication
 */

interface AuthenticationManagerInterface
{
    /**
     * Handles all messages for authentication (Hello and Authenticate)
     * This is called by the Realm to handle authentication
     * 
     * @param \Thruway\Realm $realm
     * @param \Thruway\Session $session
     * @param \Thruway\Message\Message $msg
     */
    public function onAuthenticationMessage(Realm $realm, Session $session, Message $msg);

    /**
     * Handle close session
     * 
     * @param \Thruway\Session $session
     */
    public function onSessionClose(Session $session);

    /**
     * Get list supported authention methods
     * 
     * @return array
     */
    public function getAuthMethods();
}
