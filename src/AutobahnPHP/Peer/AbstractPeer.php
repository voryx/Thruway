<?php

namespace AutobahnPHP\Peer;

use AutobahnPHP\AbstractSession;
use AutobahnPHP\Message\HelloMessage;
use AutobahnPHP\Message\Message;

/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:55 AM
 */
abstract class AbstractPeer
{

    /**
     * @param AbstractSession $session
     * @param $msg
     */
    public function onRawMessage(AbstractSession $session, $msg)
    {
        echo "Raw message... (" . $msg . ")\n";

        $msgObj = Message::createMessageFromRaw($msg);

        $this->onMessage($session, $msgObj);
    }

    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    abstract public function onMessage(AbstractSession $session, Message $msg);
}