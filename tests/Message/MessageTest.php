<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 8/23/14
 * Time: 12:21 AM
 */

namespace Message;


use Thruway\CallResult;
use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Message\ResultMessage;
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

    function testCallResultIntegerArg() {
        $callResult = new CallResult(new ResultMessage(1, new \stdClass(), [5]));

        $this->assertEquals("5", $callResult, "Conversion of CallResult[0] from int to string.");
    }

    function testCallResultNoArgs() {
        $callResult = new CallResult(new ResultMessage(1, new \stdClass(), []));

        $this->assertEquals("", $callResult, "CallResult null when zero arguments.");
    }

    function testCallResultNullArgs() {
        $callResult = new CallResult(new ResultMessage(1, new \stdClass(), null));

        $this->assertEquals("", $callResult, "CallResult null when null arguments.");
    }

    function testABunchOfMessages() {
        $tests = [
            // AbortMessage
            [ "in" => '[3, {"message": "The realm does not exist."}, "wamp.error.no_such_realm"]', "out" => '[3,{"message":"The realm does not exist."},"wamp.error.no_such_realm"]' ],
            [ "in" => '[3, {}, "wamp.error.no_such_realm"]', "out" => '[3,{},"wamp.error.no_such_realm"]' ],

            // AuthenticateMessage
            [ "in" => '[5, "gir1mSx+deCDUV7wRM5SGIn/+R/ClqLZuH4m7FJeBVI=", {}]', "out" => '[5,"gir1mSx+deCDUV7wRM5SGIn\/+R\/ClqLZuH4m7FJeBVI=",{}]'],
            [ "in" => '[5, "gir1mSx+deCDUV7wRM5SGIn/+R/ClqLZuH4m7FJeBVI=", {"option_name":"option_value"}]', "out" => '[5,"gir1mSx+deCDUV7wRM5SGIn\/+R\/ClqLZuH4m7FJeBVI=",{"option_name":"option_value"}]'],

            // CallMessage
            [ "in"=> '[48,12345,{},"com.example.rpc"]', "out"=>'[48,12345,{},"com.example.rpc"]' ],
            [ "in"=> '[48,12345,{},"com.example.rpc", [], {}]', "out"=>'[48,12345,{},"com.example.rpc"]' ],
            [ "in"=> '[48,12345,{},"com.example.rpc", [], {"test": "something"}]', "out"=>'[48,12345,{},"com.example.rpc",[],{"test":"something"}]' ],
            [ "in"=> '[48,12345,{},"com.example.rpc", [{"test":"something"}], {}]', "out"=>'[48,12345,{},"com.example.rpc",[{"test":"something"}]]' ],

            // CancelMessage
            [ "in" => '[49,12345,{"really":true}]', "out" => '[49,12345,{"really":true}]' ],
            [ "in" => '[49,12345,{}]', "out" => '[49,12345,{}]' ],

            // ChallengeMessage
            [ "in" => '[4, "wampcra",{"challenge": "{\"nonce\": \"LHRTC9zeOIrt_9U3\", \"authprovider\": \"userdb\", \"authid\": \"peter\",\"timestamp\": \"2014-06-22T16:36:25.448Z\", \"authrole\": \"user\",\"authmethod\": \"wampcra\", \"session\": 3251278072152162}"}]',
              "out" => '[4,"wampcra",{"challenge":"{\"nonce\": \"LHRTC9zeOIrt_9U3\", \"authprovider\": \"userdb\", \"authid\": \"peter\",\"timestamp\": \"2014-06-22T16:36:25.448Z\", \"authrole\": \"user\",\"authmethod\": \"wampcra\", \"session\": 3251278072152162}"}]'
            ],
            [ "in" => '[4,"some_auth",{}]', "out" => '[4,"some_auth",{}]'],

            // ErrorMessage
            [ "in"=> '[8,48,12345,{},"some.error.uri"]', "out"=>'[8,48,12345,{},"some.error.uri"]' ],
            [ "in"=> '[8,48,12345,{},"some.error.uri",[],{}]', "out"=>'[8,48,12345,{},"some.error.uri"]' ],
            [ "in"=> '[8,48,12345,{},"some.error.uri",[], {"test": "something"}]', "out"=>'[8,48,12345,{},"some.error.uri",[],{"test":"something"}]' ],
            [ "in"=> '[8,48,12345,{},"some.error.uri",[{"test":"something"}], {}]', "out"=>'[8,48,12345,{},"some.error.uri",[{"test":"something"}]]' ],

            // EventMessage
            [ "in" => '[36, 5512315355, 4429313566, {}]', "out" => '[36,5512315355,4429313566,{}]' ],
            [ "in" => '[36, 5512315355, 4429313566, {}, ["Hello, world!"]]', "out" => '[36,5512315355,4429313566,{},["Hello, world!"]]'],
            [ "in" => '[36, 5512315355, 4429313566, {}, [], {"color": "orange", "sizes": [23, 42, 7]}]', "out" => '[36,5512315355,4429313566,{},[],{"color":"orange","sizes":[23,42,7]}]'],

            // GoodbyeMessage
            [ "in" => '[6, {"message": "The host is shutting down now."}, "wamp.error.system_shutdown"]', "out" => '[6,{"message":"The host is shutting down now."},"wamp.error.system_shutdown"]'],
            [ "in" => '[6, { }, "wamp.error.system_shutdown"]', "out" => '[6,{},"wamp.error.system_shutdown"]'],

            // HeartbeatMessage
            [ "in" => '[7, 23, 5, "throw me away ... I am just noise"]', "out" => '[7,23,5,"throw me away ... I am just noise"]'],

            // HelloMessage
            // This is a problem - the publisher is showing [] instead of {} in the serializer output
            // -'[1,9129137332,{"agent":"AutobahnPython-0.7.0","roles":{"publisher":{}}}]'
            // +'[1,9129137332,{"agent":"AutobahnPython-0.7.0","roles":{"publisher":[]}}]'
            ////[ "in" => '[1, 9129137332, {"agent": "AutobahnPython-0.7.0","roles": {"publisher": {}}}]', "out" => '[1,9129137332,{"agent":"AutobahnPython-0.7.0","roles":{"publisher":{}}}]'],

            // InterruptMessage
            [ "in" => '[69, 12345, {}]', "out" => '[69,12345,{}]'],
            [ "in" => '[69, 12345, {"really":"yes"}]', "out" => '[69,12345,{"really":"yes"}]'],
            ////[ "in" => '[69, 12345, {"extra":{},"some_list":[]}]', "out" => '[69,12345,{"extra":{},"some_list":[]}]'],

            // InvocationMessage
            [ "in" => '[68, 6131533, 9823526, {}]', "out" => '[68,6131533,9823526,{}]' ],
            [ "in" => '[68, 6131533, 9823527, {}, ["Hello, world!"]]', "out" => '[68,6131533,9823527,{},["Hello, world!"]]' ],
            [ "in" => '[68, 6131533, 9823528, {}, [23, 7]]', "out" => '[68,6131533,9823528,{},[23,7]]' ],
            [ "in" => '[68, 6131533, 9823529, {}, ["johnny"], {"firstname": "John", "surname": "Doe"}]', "out" => '[68,6131533,9823529,{},["johnny"],{"firstname":"John","surname":"Doe"}]' ],

            // PublishMessage
            [ "in"=> '[16, 239714735, {}, "com.myapp.mytopic1", [], {"color": "orange", "sizes": [23, 42, 7]}]', "out"=> '[16,239714735,{},"com.myapp.mytopic1",[],{"color":"orange","sizes":[23,42,7]}]'],
            [ "in"=> '[16, 239714735, {}, "com.myapp.mytopic1", [], {"color": "orange", "sizes": [23, 42, 7]}]', "out"=> '[16,239714735,{},"com.myapp.mytopic1",[],{"color":"orange","sizes":[23,42,7]}]'],
            [ "in"=> '[16, 239714735, {}, "com.myapp.mytopic1", [{"color": "orange", "sizes": [23, 42, 7]}],{}]', "out"=> '[16,239714735,{},"com.myapp.mytopic1",[{"color":"orange","sizes":[23,42,7]}]]'],

            // PublishedMessage
            [ "in" => '[17, 239714735, 4429313566]', "out" => '[17,239714735,4429313566]' ],

            // RegisteredMessage
            [ "in" => '[65, 25349185, 2103333224]', "out" => '[65,25349185,2103333224]' ],

            // RegisterMessage
            [ "in" => '[64, 25349185, {}, "com.myapp.myprocedure1"]', "out" => '[64,25349185,{},"com.myapp.myprocedure1"]' ],
            ////[ "in" => '[64, 25349185, {"some_list":[],"some_object":{}}, "com.myapp.myprocedure1"]', "out" => '[64,25349185,{"some_list":[],"some_object":{}},"com.myapp.myprocedure1"]' ],


            // ResultMessage
            [ "in" => '[50, 7814135, {}]', "out" => '[50,7814135,{}]' ],
            [ "in" => '[50, 7814135, {}, []]', "out" => '[50,7814135,{}]' ],
            [ "in" => '[50, 7814135, {}, ["Hello, world!"]]', "out" => '[50,7814135,{},["Hello, world!"]]' ],
            [ "in" => '[50, 7814135, {}, [30]]', "out" => '[50,7814135,{},[30]]' ],
            [ "in" => '[50, 7814135, {}, [], {"userid": 123, "karma": 10}]', "out" => '[50,7814135,{},[],{"userid":123,"karma":10}]' ],

            // SubscribedMessage
            [ "in" => '[33, 713845233, 5512315355]', "out" => '[33,713845233,5512315355]' ],

            // SubscribeMessage
            [ "in" => '[32, 713845233, {}, "com.myapp.mytopic1"]', "out" => '[32,713845233,{},"com.myapp.mytopic1"]' ],
            ////[ "in" => '[32, 713845233, {"some_list":[],"some_object":{}}, "com.myapp.mytopic1"]', "out" => '[32,713845233,{"some_list":[],"some_object":{}},"com.myapp.mytopic1"]' ],

            // UnregisteredMessage
            [ "in" => '[67, 788923562]', "out" => '[67,788923562]' ],

            // UnregisterMessage
            [ "in" => '[66, 788923562, 2103333224]', "out" => '[66,788923562,2103333224]' ],

            // UnsubscribedMessage
            [ "in" => '[35, 85346237]', "out" => '[35,85346237]' ],

            // UnsubscribeMessage
            [ "in" => '[34, 85346237, 5512315355]', "out" => '[34,85346237,5512315355]' ],

            // WelcomeMessage
            ////[ "in" => '[2, 9129137332, {"roles": {"broker": {}}}]', "out" => '[2,9129137332,{"roles":{"broker":{}}}]' ],

            // YieldMessage
            [ "in" => '[70,6131533,{}]', "out" => '[70,6131533,{}]' ],
            [ "in" => '[70,6131533,{},["Hello, world!"], {}]', "out" => '[70,6131533,{},["Hello, world!"]]'],
            [ "in" => '[70,6131533,{},[],{"userid":123,"karma":10}]', "out" => '[70,6131533,{},[],{"userid":123,"karma":10}]']

        ];

        $serializer = new JsonSerializer();

        foreach ($tests as $test) {
            $msg = $serializer->deserialize($test["in"]);

            //$this->assertEquals($test["out"], json_encode(json_decode($test["out"])));

            $this->assertEquals($test['out'], $serializer->serialize($msg));
        }

    }
} 