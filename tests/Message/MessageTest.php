<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/23/14
 * Time: 12:21 AM
 */

namespace Message;


use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Serializer\JsonSerializer;

class MessageTest extends \PHPUnit_Framework_TestCase {
    function testSomething() {
        $this->assertTrue(true);
    }
    function xtestPingMessage() {
        $rawPing = "[260, 12345]";
        $pingMsg = Message::createMessageFromRaw($rawPing);

        $this->assertTrue($pingMsg instanceof PingMessage, "Serialized to PingMessage class");
        $this->assertTrue($pingMsg->getRequestId() == 12345, "Request id preserved");

        $this->assertEquals(json_encode(json_decode($rawPing)),
            $pingMsg->getSerializedMessage(),
            "Serialized version matches"
        );

        $rawPing = "[260, 12345, {\"test\": \"good\"}, [67890], \"discard string\"]";
        $pingMsg = Message::createMessageFromRaw($rawPing);

        $this->assertTrue($pingMsg instanceof PingMessage, "Serialized to PingMessage class");
        /** @var $pingMsg PingMessage */
        $this->assertTrue($pingMsg->getRequestId() == 12345, "Request id preserved");
        $this->assertTrue($pingMsg->getOptions()['test'] == "good", "Details deserialized correctly");
        $this->assertTrue($pingMsg->getEcho()[0] == "67890", "Echo deserialized correctly");
        $this->assertTrue($pingMsg->getDiscard() == "discard string", "Echo deserialized correctly");

        $pongMsg = $pingMsg->getPong();

        $this->assertEquals(12345, $pongMsg->getRequestId(), "Pong created with correct request id");
        $this->assertEquals(67890, $pongMsg->getEcho()[0], "Echo in the pong is correct");

        $this->assertEquals(json_encode(json_decode($rawPing)),
            $pingMsg->getSerializedMessage(),
            "Serialized version matches"
        );
    }

    function xtestPongMessage() {
        $rawPong = "[261, 12345]";
        $pongMsg = Message::createMessageFromRaw($rawPong);

        $this->assertTrue($pongMsg instanceof PongMessage, "Serialized to PongMessage class");
        $this->assertTrue($pongMsg->getRequestId() == 12345, "Request id preserved");

        $this->assertEquals(json_encode(json_decode($rawPong)),
            $pongMsg->getSerializedMessage(),
            "Serialized version matches"
        );

        $rawPong = "[261, 12345, {\"test\": \"good\"}, [67890]]";
        $pongMsg = Message::createMessageFromRaw($rawPong);

        $this->assertTrue($pongMsg instanceof PongMessage, "Serialized to PongMessage class");
        /** @var $pongMsg PongMessage */
        $this->assertTrue($pongMsg->getRequestId() == 12345, "Request id preserved");
        $this->assertTrue($pongMsg->getDetails()['test'] == "good", "Details deserialized correctly");
        $this->assertTrue($pongMsg->getEcho()[0] == "67890", "Echo deserialized correctly");

        $this->assertEquals(json_encode(json_decode($rawPong)),
            $pongMsg->getSerializedMessage(),
            "Serialized version matches"
        );
    }

    function testErrorMessage() {
        $errorMsg = new ErrorMessage(Message::MSG_INVOCATION, 12345, new \stdClass(),
            "some.error", array("some", "error"), array("some" => "error")
        );

        $serializer = new JsonSerializer();

        $deserialized = $serializer->deserialize("[8,68,12345,{},\"some.error\",[\"some\",\"error\"],{\"some\":\"error\"}]");

        $this->assertEquals("[8,68,12345,{},\"some.error\",[\"some\",\"error\"],{\"some\":\"error\"}]",$serializer->serialize($errorMsg));
        $this->assertEquals("[8,68,12345,{},\"some.error\",[\"some\",\"error\"],{\"some\":\"error\"}]",$serializer->serialize($deserialized));
    }

    function testCallMessage() {
        $tests = [
            // CallMessage
            [ "in"=> '[48,12345,{},"com.example.rpc"]', "out"=>'[48,12345,{},"com.example.rpc"]' ],
            [ "in"=> '[48,12345,{},"com.example.rpc", [], {}]', "out"=>'[48,12345,{},"com.example.rpc"]' ],
            [ "in"=> '[48,12345,{},"com.example.rpc", [], {"test": "something"}]', "out"=>'[48,12345,{},"com.example.rpc",[],{"test":"something"}]' ],
            [ "in"=> '[48,12345,{},"com.example.rpc", [{"test":"something"}], {}]', "out"=>'[48,12345,{},"com.example.rpc",[{"test":"something"}]]' ],

            // ErrorMessage
            [ "in"=> '[8,48,12345,{},"some.error.uri"]', "out"=>'[8,48,12345,{},"some.error.uri"]' ],
            [ "in"=> '[8,48,12345,{},"some.error.uri",[],{}]', "out"=>'[8,48,12345,{},"some.error.uri"]' ],
            [ "in"=> '[8,48,12345,{},"some.error.uri",[], {"test": "something"}]', "out"=>'[8,48,12345,{},"some.error.uri",[],{"test":"something"}]' ],
            [ "in"=> '[8,48,12345,{},"some.error.uri",[{"test":"something"}], {}]', "out"=>'[8,48,12345,{},"some.error.uri",[{"test":"something"}]]' ],

            // PublishMessage
            [ "in"=> '[16, 239714735, {}, "com.myapp.mytopic1", [], {"color": "orange", "sizes": [23, 42, 7]}]', "out"=> '[16,239714735,{},"com.myapp.mytopic1",[],{"color":"orange","sizes":[23,42,7]}]'],
            [ "in"=> '[16, 239714735, {}, "com.myapp.mytopic1", [], {"color": "orange", "sizes": [23, 42, 7]}]', "out"=> '[16,239714735,{},"com.myapp.mytopic1",[],{"color":"orange","sizes":[23,42,7]}]'],
            [ "in"=> '[16, 239714735, {}, "com.myapp.mytopic1", [{"color": "orange", "sizes": [23, 42, 7]}],{}]', "out"=> '[16,239714735,{},"com.myapp.mytopic1",[{"color":"orange","sizes":[23,42,7]}]]'],

            // EventMessage
            [ "in" => '[36, 5512315355, 4429313566, {}]', "out" => '[36,5512315355,4429313566,{}]' ],
            [ "in" => '[36, 5512315355, 4429313566, {}, ["Hello, world!"]]', "out" => '[36,5512315355,4429313566,{},["Hello, world!"]]'],
            [ "in" => '[36, 5512315355, 4429313566, {}, [], {"color": "orange", "sizes": [23, 42, 7]}]', "out" => '[36,5512315355,4429313566,{},[],{"color":"orange","sizes":[23,42,7]}]'],

            // ResultMessage
            [ "in" => '[50, 7814135, {}]', "out" => '[50,7814135,{}]' ],
            [ "in" => '[50, 7814135, {}, ["Hello, world!"]]', "out" => '[50,7814135,{},["Hello, world!"]]' ],
            [ "in" => '[50, 7814135, {}, [30]]', "out" => '[50,7814135,{},[30]]' ],
            [ "in" => '[50, 7814135, {}, [], {"userid": 123, "karma": 10}]', "out" => '[50,7814135,{},[],{"userid":123,"karma":10}]' ],

            // InvocationMessage
            [ "in" => '[68, 6131533, 9823526, {}]', "out" => '[68,6131533,9823526,{}]' ],
            [ "in" => '[68, 6131533, 9823527, {}, ["Hello, world!"]]', "out" => '[68,6131533,9823527,{},["Hello, world!"]]' ],
            [ "in" => '[68, 6131533, 9823528, {}, [23, 7]]', "out" => '[68,6131533,9823528,{},[23,7]]' ],
            [ "in" => '[68, 6131533, 9823529, {}, ["johnny"], {"firstname": "John", "surname": "Doe"}]', "out" => '[68,6131533,9823529,{},["johnny"],{"firstname":"John","surname":"Doe"}]' ],

            // YieldMessage
            [ "in" => '[70,6131533,{}]', "out" => '[70,6131533,{}]' ],
            [ "in" => '[70,6131533,{},["Hello, world!"], {}]', "out" => '[70,6131533,{},["Hello, world!"]]'],
            [ "in" => '[70,6131533,{},[],{"userid":123,"karma":10}]', "out" => '[70,6131533,{},[],{"userid":123,"karma":10}]']

        ];

        $serializer = new JsonSerializer();

        foreach ($tests as $test) {
            $msg = $serializer->deserialize($test["in"]);

            $this->assertEquals($test['out'], $serializer->serialize($msg));
        }

    }
} 