<?php


class SessionTest extends PHPUnit_Framework_TestCase {
    public function testTransportSendMessage() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $session = new \Thruway\Session($transport);

        $transport->expects($this->exactly(2))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\GoodbyeMessage')],
                [$this->isInstanceOf('\Thruway\Message\GoodbyeMessage')]
            );

        $session->sendMessage(new \Thruway\Message\GoodbyeMessage([], "test.message"));
        $session->sendMessage(new \Thruway\Message\GoodbyeMessage([], "test.message"));
    }
} 