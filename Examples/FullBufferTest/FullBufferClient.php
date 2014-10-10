<?php


/**
 * Class FullBufferClient
 */
class FullBufferClient extends \Thruway\Peer\Client
{

    /**
     * @param array $args
     */
    public function onBufferFill($args)
    {
        var_dump($args);
    }

    /**
     * Handle on session start
     * 
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->getSubscriber()->subscribe($session, 'bufferFill', [$this, 'onBufferFill']);
    }

}