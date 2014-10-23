<?php


namespace Thruway\Message;


/**
 * Interface ActionMessageInterface
 *
 * This interface is used on the 4 messages that require authorization
 * Publish, Subscribe, Register, Call
 *
 * @package Thruway\Message
 */
interface ActionMessageInterface {
    /**
     * This returns the Uri so that the authorization manager doesn't have to know
     * exactly the type of object to get the Uri
     *
     * @return mixed
     */
    public function getUri();

    /**
     * This returns the action name "publish", "subscribe", "register", "call"
     *
     * @return mixed
     */
    public function getActionName();
} 