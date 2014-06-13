<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 11:58 AM
 */

namespace AutobahnPHP\Peer;


use AutobahnPHP\Message\Message;
use AutobahnPHP\Session;

/**
 * Class Client
 * @package AutobahnPHP
 */
class Client extends AbstractPeer
{
    /**
     * @param Session $session
     * @param Message $msg
     */
    public function onMessage(Session $session, Message $msg)
    {
        // TODO: Implement onMessage() method.
    }

} 