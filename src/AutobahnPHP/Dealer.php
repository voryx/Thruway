<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\Message;

class Dealer extends AbstractRole {

    public function onMessage(Session $session, Message $msg)
    {
        // TODO: Implement onMessage() method.
    }

    public function handlesMessage(Message $msg)
    {
        return false;
    }
}