<?php

class HelloMessageTest extends PHPUnit\Framework\TestCase
{
    /**
     * @expectedException InvalidArgumentException
     * @throws \Thruway\Message\MessageException
     */
    public function testObjectAsRealmName()
    {
        $msg = \Thruway\Message\Message::createMessageFromArray(
            [\Thruway\Message\Message::MSG_HELLO, new stdClass(), new stdClass()]
        );
    }
}
