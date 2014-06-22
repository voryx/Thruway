<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/18/14
 * Time: 10:32 PM
 */

namespace Thruway\Transport;


use Thruway\Message\Message;

interface TransportInterface {
    public function getTransportDetails();
    public function sendMessage(Message $msg);
    public function close();
} 