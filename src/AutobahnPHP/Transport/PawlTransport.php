<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/19/14
 * Time: 12:03 AM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\Message\Message;
use Ratchet\Client\WebSocket;
use Ratchet\ConnectionInterface;

class PawlTransport implements TransportInterface {

    /**
     * @var WebSocket
     */
    private $conn;

    function __construct($conn)
    {
        $this->conn = $conn;
    }


    public function sendMessage(Message $msg)
    {
        $this->conn->send($msg->getSerializedMessage());
    }

    public function close()
    {
        $this->conn->close();
    }

} 