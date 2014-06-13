<?php

namespace AutobahnPHP\Peer;
use AutobahnPHP\Message\HelloMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Session;

/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:55 AM
 */

abstract class AbstractPeer {

    /**
     * @param Session $session
     * @param $msg
     */
    public function onRawMessage(Session $session, $msg) {
        echo "Raw message... (" . $msg . ")\n";

        $msgObj = Message::createMessageFromRaw($msg);

        $this->onMessage($session, $msgObj);
    }

    abstract public function onMessage(Session $session, Message $msg);
}