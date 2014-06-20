<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 10:12 PM
 */

namespace Thruway\Role;


use Thruway\AbstractSession;
use Thruway\Message\Message;
use Thruway\Session;

/**
 * Class AbstractRole
 * @package Thruway\Role
 */
abstract class AbstractRole
{
    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    abstract public function onMessage(AbstractSession $session, Message $msg);

    /**
     * @param Message $msg
     * @return mixed
     */
    abstract public function handlesMessage(Message $msg);
} 