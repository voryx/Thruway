<?php

class EndToEndTest extends PHPUnit_Framework_TestCase
{

    protected $_conn;
    protected $_error;

    public function setUp()
    {
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
        $this->_error = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.ping', ['testing123'])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->assertEquals('testing123', $res[0]);
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
    }

    /**
     * @depends testCall
     */
    public function testSubscribe()
    {
        $this->_error = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                /**
                 * Subscribe to event
                 */
                $session->subscribe(
                    'com.example.publish',
                    function ($args) {
                        $this->_conn->close();
                        $this->assertEquals('test publish', $args[0]);
                    }
                );

                /**
                 * Tell the server to publish
                 */
                $session->call('com.example.publish', ['test publish'])->then(
                    function ($res) {

                        $this->assertEquals('ok', $res[0]);
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
    }
}