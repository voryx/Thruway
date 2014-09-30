<?php

namespace Thruway\Role;


use Thruway\AbstractSession;
use Thruway\Message\Message;
use Thruway\Session;

/**
 * Class AbstractRole
 *
 * @package Thruway\Role
 */
abstract class AbstractRole
{

    /**
     * Handle process reveiced message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return mixed
     */
    abstract public function onMessage(AbstractSession $session, Message $msg);

    /**
     * Handle process message
     *
     * @param \Thruway\Message\Message $msg
     * @return mixed
     */
    abstract public function handlesMessage(Message $msg);

    /**
     * Strict URI Test
     *
     * @param $uri
     * @return boolean
     */
    public function uriIsValid($uri)
    {
        return !!preg_match('/^([0-9a-z_]+\.)*([0-9a-z_]+)$/', $uri);
    }

} 