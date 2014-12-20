<?php

/**
 * This example is from the blog post:
 * @see http://voryx.net/creating-a-custom-php-wamp-client-for-thruway/
 */
class FreeSpaceClient extends Thruway\Peer\Client
{

    /**
     * @return array
     */
    public function getFreeSpace()
    {
        return [disk_free_space('/')]; // use c: for you windowers
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $session->register('com.example.getfreespace', [$this, 'getFreeSpace']);
    }

}

