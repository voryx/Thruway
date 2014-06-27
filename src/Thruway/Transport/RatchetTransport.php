<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/18/14
 * Time: 10:36 PM
 */

namespace Thruway\Transport;


use Thruway\Message\Message;
use Ratchet\ConnectionInterface;

class RatchetTransport implements TransportInterface {
    /**
     * @var ConnectionInterface
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

    public function getTransportDetails()
    {
        $transportAddress = $this->conn->remoteAddress;

        $details = array(
            "type" => "ratchet",
            "transportAddress" => $transportAddress
        );

        return $details;
    }


} 