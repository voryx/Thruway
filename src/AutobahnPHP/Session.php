<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/8/14
 * Time: 11:15 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\Message;
use AutobahnPHP\Transport\TransportInterface;
use Ratchet\ConnectionInterface;

/**
 * Class Session
 * @package AutobahnPHP
 */
class Session extends AbstractSession
{

    /**
     * @var
     */
    private $authenticationProvider;



    function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
        $this->state = static::STATE_PRE_HELLO;
        $this->sessionId = static::getUniqueId();
        $this->realm = null;
    }

    public function sendMessage(Message $msg)
    {
        $this->transport->sendMessage($msg);
    }

    public function shutdown()
    {

        $this->transport->close();
    }

    /**
     * @return mixed
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * @param mixed $authenticationProvider
     */
    public function setAuthenticationProvider($authenticationProvider)
    {
        $this->authenticationProvider = $authenticationProvider;
    }


    /**
     *
     */
    public function onClose()
    {
        if ($this->realm !== null) {
            $this->realm->leave($this);
        }
    }

    /**
     * Generate a unique id for sessions and requests
     * @return mixed
     */
    static public function getUniqueId()
    {
        // TODO: make this better
        $result = sscanf(uniqid(), "%x");

        return $result[0];
    }

} 