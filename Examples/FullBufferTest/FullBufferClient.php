<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 7/22/14
 * Time: 9:57 PM
 */

class FullBufferClient extends \Thruway\Peer\Client {
    public function onBufferFill($args) {
        var_dump($args);
    }

    public function onSessionStart($session, $transport) {
        $this->getSubscriber()->subscribe($session, 'bufferFill', array($this, 'onBufferFill'));
    }
} 