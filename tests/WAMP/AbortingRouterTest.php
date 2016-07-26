<?php

class AbortingRouterTest extends \PHPUnit_Framework_TestCase {
    public function testAbortFollowingHello() {
        $this->_testResult = null;
        $this->_error = null;

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'abortafterhello',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["abortafterhello"],
                "onChallenge" => function () {}
            )
        );

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $this->_testResult = "connection opened.";
                $this->_conn->close();
            }
        );

        $this->_conn->on(
            'error',
            function ($reason) {
                $this->_testResult = $reason;
            }
        );

        $this->_conn->open();

        $this->assertEquals("thruway.error.authentication_failure", $this->_testResult);
    }

    public function testAbortFollowingHelloWithDetails() {
        $this->_testResult = null;
        $this->_error = null;

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'abortafterhellowithdetails',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["abortafterhellowithdetails"],
                "onChallenge" => function () {}
            )
        );

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $this->_testResult = "connection opened.";
                $this->_conn->close();
            }
        );

        $this->_conn->on(
            'error',
            function ($reason, \Thruway\Message\AbortMessage $msg = null) {
                $this->assertInstanceOf('\Thruway\Message\AbortMessage', $msg);
                $this->assertEquals("my.custom.abort.uri", $msg->getResponseURI());
                $this->assertEquals("My custom abort message", $msg->getDetails()->message);
                $this->_testResult = $reason;
            }
        );

        $this->_conn->open();

        $this->assertEquals("my.custom.abort.uri", $this->_testResult);
    }

    public function testAbortFollowingAuthenticateWithDetails() {
        $this->_testResult = null;
        $this->_error = null;

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'aaawd',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["abortafterauthenticatewithdetails"],
                "onChallenge" => function () { return 'asdf'; }
            )
        );

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $this->_testResult = "connection opened.";
                $this->_conn->close();
            }
        );

        $this->_conn->on(
            'error',
            function ($reason, \Thruway\Message\AbortMessage $msg = null) {
                $this->assertInstanceOf('\Thruway\Message\AbortMessage', $msg);
                $this->assertEquals("my.custom.abort.uri.2", $msg->getResponseURI());
                $this->assertEquals("My custom abort message 2", $msg->getDetails()->message);
                $this->_testResult = $reason;
            }
        );

        $this->_conn->open();

        $this->assertEquals("my.custom.abort.uri.2", $this->_testResult);
    }
}