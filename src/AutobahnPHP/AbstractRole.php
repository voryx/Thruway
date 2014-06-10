<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 10:12 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\Message;

abstract class AbstractRole {
    abstract public function onMessage(Session $session, Message $msg);
    abstract public function handlesMessage(Message $msg);
} 