<?php


/**
 * Class FullBufferClient
 */
class FullBufferClient extends \Thruway\Peer\Client
{

    /**
     * @param $args
     */
    public function onBufferFill($args)
    {
        var_dump($args);
    }

    /**
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->getSubscriber()->subscribe($session, 'bufferFill', [$this, 'onBufferFill']);
    }

}