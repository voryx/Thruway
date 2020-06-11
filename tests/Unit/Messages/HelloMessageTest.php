<?php

namespace Thruway\Tests\Unit\Messages;

class HelloMessageTest extends \Thruway\Tests\TestCase
{
    /**
     * @throws \Thruway\Message\MessageException
     */
    public function testObjectAsRealmName()
    {
        $this->expectException('\InvalidArgumentException');
        $msg = \Thruway\Message\Message::createMessageFromArray(
            [\Thruway\Message\Message::MSG_HELLO, new \stdClass(), new \stdClass()]
        );
    }
}