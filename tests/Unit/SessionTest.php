<?php


class SessionTest extends PHPUnit_Framework_TestCase {
    public function testTransportSendMessage() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $session = new \Thruway\Session($transport);

        $transport->expects($this->exactly(2))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\HelloMessage')],
                [$this->isInstanceOf('\Thruway\Message\HelloMessage')]
            );

        $session->sendMessage(new \Thruway\Message\HelloMessage("realm1", (object)[]));


        $session->sendMessage(new \Thruway\Message\HelloMessage("realm1", (object)[]));
    }
} 