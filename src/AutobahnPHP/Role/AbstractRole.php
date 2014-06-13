<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 10:12 PM
 */

namespace AutobahnPHP\Role;


use AutobahnPHP\Message\Message;
use AutobahnPHP\Session;

/**
 * Class AbstractRole
 * @package AutobahnPHP\Role
 */
abstract class AbstractRole
{
    /**
     * @param Session $session
     * @param Message $msg
     * @return mixed
     */
    abstract public function onMessage(Session $session, Message $msg);

    /**
     * @param Message $msg
     * @return mixed
     */
    abstract public function handlesMessage(Message $msg);
} 