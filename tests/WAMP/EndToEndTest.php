<?php

class EndToEndTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Thruway\Connection
     */
    protected $_conn;
    protected $_error;
    protected $_testArgs;
    protected $_testKWArgs;
    protected $_publicationId;
    protected $_details;
    protected $_testResult;
    protected $_echoResult;

    public function setUp()
    {
        $this->testArgs = null;
        $this->_testResult = null;
        $this->_error = null;

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'testRealm',
                "url" => 'ws://127.0.0.1:8080',
                "max_retries" => 0,
            )
        );
    }


    public function testCall()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.testcall', ['testing123'])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = $res;
                    },
                    function ($error) {
                        $this->_conn->close();
                        $this->_error = $error;
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals('testing123', $this->_testResult[0]);
    }

    public function testPing()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->ping(10,new \stdClass(),["echo content"],"discard")->then(
                    function ($res) {
                        /** @var $res \Thruway\Message\PongMessage */
                        $this->_conn->close();
                        $this->_testResult = $res;
                        $this->_echoResult = $res->getEcho();

                        var_dump($res);
                    },
                    function ($error) {
                        $this->_conn->close();
                        $this->_error = $error;
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when pinging: {$this->_error}");
        $this->assertEquals($this->_echoResult[0], "echo content", "Ping echoed correctly");
        $this->assertTrue($this->_testResult instanceof \Thruway\Message\PongMessage);
    }

    /**
     * This calls an RPC in the InternalClient object that calls ping from the server
     * side and returns the result.
     */
    public function testServerPing()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.ping', [])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = $res;
                    },
                    function ($error) {
                        $this->_conn->close();
                        $this->_error = $error;
                    }
                );
            }
        );

        $this->_conn->open();

        var_dump($this->_testResult);

//        $this->assertNull($this->_error, "Got this error when pinging: {$this->_error}");
//        $this->assertEquals($this->_echoResult[0], "echo content", "Ping echoed correctly");
//        $this->assertTrue($this->_testResult instanceof \Thruway\Message\PongMessage);
    }

    /**
     * @depends testCall
     */
    public function testSubscribe()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                /**
                 * Subscribe to event
                 */
                $session->subscribe(
                    'com.example.publish',
                    function ($args, $kwargs = null, $details = null, $publicationId = null) {
                        $this->_conn->close();
                        $this->_testArgs = $args;
                        $this->_testKWArgs = $kwargs;
                        $this->_publicationId = $publicationId;

                    }
                );

                /**
                 * Tell the server to publish
                 */
                $session->call('com.example.publish', ['test publish'])->then(
                    function ($res) {
                        $this->_testResult = $res;

                    },
                    function ($error) {
                        $this->_conn->close();
                        $this->_error = $error;
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals('test publish', $this->_testArgs[0]);
        $this->assertEquals('test1', $this->_testKWArgs['key1']);
        $this->assertNotNull($this->_publicationId);
        $this->assertEquals('ok', $this->_testResult[0]);
    }
}