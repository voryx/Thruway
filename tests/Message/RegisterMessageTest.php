<?php

namespace Message;


use Thruway\Message\RegisterMessage;

/**
 * Class RegisterMessageTest
 * @package Message
 *
 * <code>[REGISTER, Request|id, Options|dict, Procedure|uri]</code>
 */
class RegisterMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testEmptyArrayOptions()
    {
        $msg = new RegisterMessage(12345, [], 'com.test.register');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[64,12345,{},"com.test.register"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

    public function testArrayOptions()
    {
        $msg = new RegisterMessage(12345, ["disclose_caller" => true], 'com.test.register');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[64,12345,{"disclose_caller":true},"com.test.register"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

    public function testEmptyStdClassOptions()
    {
        $options = new \stdClass();

        $msg = new RegisterMessage(12345, $options, 'com.test.register');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[64,12345,{},"com.test.register"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

    public function testStdClassOptions()
    {
        $options = new \stdClass();

        $options->disclose_caller = true;

        $msg = new RegisterMessage(12345, $options, 'com.test.register');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[64,12345,{"disclose_caller":true},"com.test.register"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

}