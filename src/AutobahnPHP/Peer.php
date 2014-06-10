<?php

namespace AutobahnPHP;
use AutobahnPHP\Message\HelloMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\WelcomeMessage;

/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:55 AM
 */

class Peer {
    /**
     * @var \SplObjectStorage
     */
    private $roles;

    function __construct()
    {
        $this->roles = new \SplObjectStorage();
    }

    public function addRole(AbstractRole $role) {
        $this->roles->attach($role);
    }

    /**
     * @param Session $session
     * @param $msg
     */
    public function onRawMessage(Session $session, $msg) {
        echo "Raw message... (" . $msg . ")\n";

        $msgObj = Message::createMessageFromRaw($msg);

        // see if this is something we need to deal with
        // maybe make a session management role
        if ($msgObj instanceof HelloMessage) {
            if ($this instanceof Router) throw new \Exception("You don't say hello to a router.");
            $session->sendMessage(new WelcomeMessage($sessionId, new \stdClass()));
        } elseif ($msgObj instanceof WelcomeMessage) {
        } else {

            $this->roles->rewind();
            while ($this->roles->valid()) {
                $role = $this->roles->current();

                // see if this role wants to deal with this
                if ($role->onMessage($session, $msgObj)) {
                    // if it dealt with the message (returned true) break out
                    break;
                }

                $this->roles->next();
            }
        }
    }
} 