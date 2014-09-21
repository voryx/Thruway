<?php

class AbortingRouterTest extends \PHPUnit_Framework_TestCase {
    public function testAbortFollowingHello() {
        $this->_testResult = null;
        $this->_error = null;

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'abortafterhello',
                "url" => 'ws://127.0.0.1:8080',
                "max_retries" => 0,
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

        $this->assertEquals("wamp.error.not_authorized", $this->_testResult);
    }
}