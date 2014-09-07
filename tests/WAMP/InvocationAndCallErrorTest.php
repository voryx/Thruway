<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 9/6/14
 * Time: 11:46 PM
 */

class InvocationAndCallErrorTest extends PHPUnit_Framework_TestCase {
    private $_conn;
    private $_testResult;
    private $_error;

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

    public function testEndToEndError() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.failure', [])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = "resolve";
                    },
                    function ($error = null) {
                        if ($error instanceof \Thruway\Message\ErrorMessage) {
                            $this->_testResult = $error->getErrorURI();
                        } else {
                            $this->_testResult = "rejected";
                        }
                        $this->_conn->close();

                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertEquals("com.example.failure.error", $this->_testResult);
    }
} 