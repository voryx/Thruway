<?php

class ReplaceOrphanedSessionTest extends PHPUnit_Framework_TestCase {
    /**
     * @var \Thruway\Connection
     */
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
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
            )
        );
    }

    public function testReplaceOrphanedSession() {

        $this->_testResult = null;
        $this->_error = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                $this->_conn->getClient()->getCallee()->register(
                    $session,
                    'com.example.orphan_testing',
                    array($this, 'callOrphanTest'),
                    ['replace_orphaned_session' => 'no']
                )->then(function ($res = null) use ($session) {
                            $this->_error = 'OrphaningClient not registered';
                            $this->_conn->close();
                        },
                    function ($msg = null) use ($session) {
                        if ($msg instanceof \Thruway\Message\ErrorMessage) {
                            if ($msg->getErrorURI() != 'wamp.error.procedure_already_exists') {
                                $this->_testResult = $msg->getErrorURI();
                                $this->_conn->close();
                            } else {
                                $this->_conn->getClient()->getCallee()->register(
                                    $session,
                                    'com.example.orphan_testing',
                                    array($this, 'callOrphanTest'),
                                    ['replace_orphaned_session' => 'yes']
                                )->then(function () use ($session) {
                                            $session->call('com.example.orphan_testing', [])->then(
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
                                        });

                            }
                        }
                    });

            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Error was {$this->_error}");
        $this->assertEquals("resolve", $this->_testResult);
    }

    public function callOrphanTest() {
        return "In the test client";
    }

} 