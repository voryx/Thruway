<?php

namespace Message;


use Thruway\Message\SubscribeMessage;

/**
 * Class SubscribeMessageTest
 * @package Message
 *
 * <code>[SUBSCRIBE, Request|id, Options|dict, Topic|uri]</code>
 */
class SubscribeMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testEmptyArrayOptions()
    {
        $msg = new SubscribeMessage(12345, [], 'com.test.subscribe');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[32,12345,{},"com.test.subscribe"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

    public function testArrayOptions()
    {
        $msg = new SubscribeMessage(12345, ["match" => "prefix"], 'com.test.subscribe');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[32,12345,{"match":"prefix"},"com.test.subscribe"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

    public function testEmptyStdClassOptions()
    {
        $options = new \stdClass();

        $msg = new SubscribeMessage(12345, $options, 'com.test.subscribe');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[32,12345,{},"com.test.subscribe"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

    public function testStdClassOptions()
    {
        $options = new \stdClass();

        $options->match = "prefix";

        $msg = new SubscribeMessage(12345, $options, 'com.test.subscribe');

        $this->assertTrue(is_object($msg->getOptions()));

        $expectedJson = '[32,12345,{"match":"prefix"},"com.test.subscribe"]';

        $this->assertEquals($expectedJson, json_encode($msg));
    }

}