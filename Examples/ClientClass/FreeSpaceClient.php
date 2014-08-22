<?php
/**
 * This example is from the blog post:
 * http://voryx.net/creating-a-custom-php-wamp-client-for-thruway/
 */
class FreeSpaceClient extends Thruway\Peer\Client {
    public function getFreeSpace() {
        return array(disk_free_space('/')); // use c: for you windowers
    }
    public function onSessionStart($session, $transport) {
        $this->getCallee()->register($session, 'com.example.getfreespace', array($this, 'getFreeSpace'));
    }
}
