<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/18/14
 * Time: 10:32 PM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\Message\Message;

interface TransportInterface {
    public function sendMessage(Message $msg);
    public function close();
} 