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
    private $_errorMsg;

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

    public function testCallFailureFromRejectedPromise() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.failure_from_rejected_promise', [])->then(
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

        $this->assertEquals("com.example.failure_from_rejected_promise.error", $this->_testResult);
    }

    public function testCallFailureFromException() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_errorMsg = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.failure_from_exception', [])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = "resolve";
                    },
                    function ($error = null) {
                        if ($error instanceof \Thruway\Message\ErrorMessage) {
                            $this->_testResult = $error->getErrorURI();
                            $this->_errorMsg = $error;
                        } else {
                            $this->_testResult = "rejected";
                        }
                        $this->_conn->close();

                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertEquals("com.example.failure_from_exception.error", $this->_testResult);
        $this->assertEquals("Exception Happened", $this->_errorMsg->getArguments()[0]);
    }

    public function testCallWithResult() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_errorMsg = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.echo_with_argskw', ['zero', 'one', 'two', 'three'],
                    [ "a" => "alpha",
                        "b" => "bravo",
                        "c" => "charlie"
                    ]
                )->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = $res;
                    },
                    function ($error = null) {
                        if ($error instanceof \Thruway\Message\ErrorMessage) {
                            $this->_testResult = $error->getErrorURI();
                            $this->_errorMsg = $error;
                        } else {
                            $this->_testResult = "rejected";
                        }
                        $this->_conn->close();

                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertTrue($this->_testResult instanceof \Thruway\CallResult, "Result is instance of CallResult");
        $this->assertEquals(4, count($this->_testResult), "ArrayObject returns correct arg count");
        $this->assertEquals("zero", $this->_testResult[0]);
        $this->assertEquals("one", $this->_testResult[1]);
        $this->assertEquals("two", $this->_testResult[2]);
        $this->assertEquals("three", $this->_testResult[3]);

        $argsKw = $this->_testResult->getArgumentsKw();

        $this->assertEquals("alpha", $argsKw['a']);
        $this->assertEquals("bravo", $argsKw['b']);
        $this->assertEquals("charlie", $argsKw['c']);
    }

    public function testCallWithResultWithPromise() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_errorMsg = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.echo_with_argskw_with_promise',
                    ['zero', 'one', 'two', 'three'],
                    [ "a" => "alpha",
                        "b" => "bravo",
                        "c" => "charlie"
                    ]
                )->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = $res;
                    },
                    function ($error = null) {
                        if ($error instanceof \Thruway\Message\ErrorMessage) {
                            $this->_testResult = $error->getErrorURI();
                            $this->_errorMsg = $error;
                        } else {
                            $this->_testResult = "rejected";
                        }
                        $this->_conn->close();

                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertTrue($this->_testResult instanceof \Thruway\CallResult, "Result is instance of CallResult");
        $this->assertEquals(4, count($this->_testResult), "ArrayObject returns correct arg count");
        $this->assertEquals("zero", $this->_testResult[0]);
        $this->assertEquals("one", $this->_testResult[1]);
        $this->assertEquals("two", $this->_testResult[2]);
        $this->assertEquals("three", $this->_testResult[3]);

        $argsKw = $this->_testResult->getArgumentsKw();

        $this->assertEquals("alpha", $argsKw['a']);
        $this->assertEquals("bravo", $argsKw['b']);
        $this->assertEquals("charlie", $argsKw['c']);


    }

    public function testCallWithProgressOption() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_errorMsg = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.progress_option', [], null, [ "receive_progress" => true ])->then(
                        function ($res) {
                            $this->_conn->close();
                            $this->_testResult = $res;
                        },
                        function ($error = null) {
                            $this->_error = "error";
                            if ($error instanceof \Thruway\Message\ErrorMessage) {
                                $this->_testResult = $error->getErrorURI();
                                $this->_errorMsg = $error;
                            } else {
                                $this->_testResult = "rejected";
                            }
                            $this->_conn->close();

                        }
                    );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error);
        $this->assertEquals("SUCCESS", $this->_testResult[0], "Successfully saw the receive_progress option");

    }

    public function testCallWithoutProgressOption() {
        $this->_testResult = null;
        $this->_error = null;
        $this->_errorMsg = null;
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.progress_option', [])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = $res;
                    },
                    function ($error = null) {
                        $this->_error = "error";
                        if ($error instanceof \Thruway\Message\ErrorMessage) {
                            $this->_testResult = $error->getErrorURI();
                            $this->_errorMsg = $error;
                        } else {
                            $this->_testResult = "rejected";
                        }
                        $this->_conn->close();

                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNotNull($this->_error);
        $this->assertEquals("com.example.progress_option.error", $this->_testResult, "Did not see receive_progress option");
        $this->assertEquals("receive_progress option not set", $this->_errorMsg->getArguments()[0], "Did not see receive_progress option");

    }
} 