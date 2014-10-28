<?php

class EndToEndTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Thruway\Connection
     */
    protected $_conn;
    /**
     * @var \Thruway\Connection
     */
    protected $_conn2;
    protected $_error;
    protected $_testArgs;
    protected $_testKWArgs;
    protected $_publicationId;
    protected $_details;
    protected $_testResult;
    protected $_echoResult;
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $_loop;

    /** @var  \React\Promise\Deferred */
    protected $_deferred;

    protected $_connOptions;

    public function setUp()
    {
        $this->_testArgs = null;
        $this->_testResult = null;
        $this->_error = null;

        $this->_connOptions = [
            "realm" => 'testRealm',
            "url" => 'ws://127.0.0.1:8090',
            "max_retries" => 0,
        ];

        $this->_conn = new \Thruway\Connection($this->_connOptions);

        $this->_loop = $this->_conn->getClient()->getLoop();

        $this->_conn2 = new \Thruway\Connection($this->_connOptions, $this->_loop);
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
        $this->assertEquals($this->_testResult[0], $this->_testResult, "__toString should be the 0 index of the result");
        $this->assertEquals('testing123', $this->_testResult[0]);
    }

    public function xtestPing()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->ping(10)->then(
                    function ($res) {
                        $this->_conn->close();
//                        $this->_testResult = $res;
//                        $this->_echoResult = $res->getEcho();
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
    public function xtestServerPing()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.ping', [])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = \Thruway\Message\Message::createMessageFromRaw(json_encode($res[0]));
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
        $this->assertTrue($this->_testResult instanceof \Thruway\Message\PongMessage);
        $this->assertEquals("echo content", $this->_testResult->getEcho()[0], "Ping echoed correctly");
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
                        //$this->_conn->close();
                        $this->_testArgs = $args;
                        $this->_testKWArgs = $kwargs;
                        $this->_publicationId = $publicationId;

                    }
                )->then(function () use ($session) {
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
                            })->then(function () use ($session) {
                                $session->close();
                            });
                    },
                    function () use ($session) {
                        $session->close();
                        throw new Exception("subscribe failed.");
                    });
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals('test publish', $this->_testArgs[0]);
        $this->assertEquals('test1', $this->_testKWArgs['key1']);
        $this->assertNotNull($this->_publicationId);
        $this->assertEquals('ok', $this->_testResult[0]);
    }


    /**
     * @depends testCall
     */
    public function testUnregister()
    {
        $this->_error = null;

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                $callback = function () {
                    return "Hello";
                };

                $session->register('com.example.somethingToUnregister', $callback)
                    ->then(function () use ($session) {
                            $session->unregister('com.example.somethingToUnregister')
                                ->then(function () {
                                        $this->_conn->close();
                                        $this->_testResult = "unregistered";
                                    },
                                    function () {
                                        $this->_conn->close();
                                        $this->_error = "Error during unregistration";
                                    }
                                );

                        },
                        function () {
                            $this->_conn->close();
                            $this->_error = "Couldn't even register the call";
                        }
                    );

                // TODO: test unregistering again
                // TODO: test unregistering a different session's registration
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals('unregistered', $this->_testResult);
    }

    function xtestRealmUnauthenticated() {
        $this->_error = null;

        $this->_testResult = "nothing";

        $conn = new \Thruway\Connection(
            [
                "realm" => 'not_allowed',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
            ]
        );

        $conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->close();
            }
        );

        $conn->on('error', function ($reason) {
            $this->_testResult = $reason;
        });

        $conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");

        $this->assertEquals('wamp.error.not_authorized', $this->_testResult);
    }

    public function xtestCallWithArguments()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.testcallwitharguments', ['testing123'])->then(
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

    public function testNotGettingPublishedEvent() {
        $this->_error = null;
        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
            $session->subscribe('com.example.topic', function ($args) {
                    $this->_error = "got message";
                })->then(
                function () use ($session) {
                    // successfully subscribed
                    // try publishing
                    $session->publish('com.example.topic', ["test"]);

                    $session->getLoop()->addTimer(1, function () use ($session) {
                            $session->close();
                        });
                },
                function () use ($session) {
                    $this->_error = "Subscribe failed";
                    $session->close();
                }
            );
        });

        $this->_conn->open();

        $this->assertNull($this->_error, "Error " . $this->_error);
    }

    public function testGettingPublishedEvent() {
        $this->_error = null;
        $this->_testResult = null;
        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
                $session->subscribe('com.example.topic', function ($args) {
                        $this->_testResult = "got message";
                    })->then(
                    function () use ($session) {
                        // successfully subscribed
                        // try publishing
                        $session->publish('com.example.topic', ["test"], null, ["exclude_me" => false]);

                        $session->getLoop()->addTimer(1, function () use ($session) {
                                $session->close();
                            });
                    },
                    function () use ($session) {
                        $this->_error = "Subscribe failed";
                        $session->close();
                    }
                );
            });

        $this->_conn->open();

        $this->assertNull($this->_error, "Error " . $this->_error);
        $this->assertEquals("got message", $this->_testResult);
    }

    public function testSubscribeFailure() {
        $this->_error = null;
        $this->_testResult = null;

        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
                $session->subscribe('!?/&', function () {})->then(
                    function () use ($session) {
                        $this->_error = "Able to subscribe to bad Uri";
                        $session->close();
                    },
                    function () use ($session) {
                        $this->_testResult = "subscribe failed";
                        $session->close();
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Error " . $this->_error);
        $this->assertEquals("subscribe failed", $this->_testResult);
    }

    public function testPublishExclude() {
        $this->_testResult = null;
        $this->_deferred = new \React\Promise\Deferred();

        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
            $session->subscribe("some.topic", function ($args) {
                $this->_testResult .= $args[0];
                if ($args[0] == 3) {
                    $this->_deferred->resolve();
                }
            })->then(function ($subscribedMsg) use ($session) {
                $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $subscribedMsg);

                $this->_conn2->on('open', function (\Thruway\ClientSession $s2) use ($session, $subscribedMsg) {
                    $promises = [];

                    $promises[] = $s2->publish("some.topic", [1], null, ['acknowledge' => true]);
                    $promises[] = $s2->publish("some.topic", [2], null, ['acknowledge' => true, 'exclude' => [$session->getSessionId()]]);
                    $promises[] = $s2->publish("some.topic", [3], null, ['acknowledge' => true]);

                    // add the subscription deferred so we can wait until we get the
                    // published events before exiting
                    $promises[] = $this->_deferred->promise();

                    $pAll = \React\Promise\all($promises);

                    $pAll->then(function () use ($session, $s2) {
                        $session->close();
                        $s2->close();
                    }, function () use ($session, $s2) {
                        $this->fail("Publish failed");
                        $session->close();
                        $s2->close();
                    });
                });

                $this->_conn2->open();
            }, function () use ($session) {
                $session->close();
                $this->fail("Subscribe failed");
            });
        });

        $this->_conn->open();

        $this->assertEquals("13", $this->_testResult);
    }

    public function testWhiteList() {

        $conns = [];
        $sessionIds = [];

        $loop = $this->_loop;

        $subscribePromises = [];

        $deferredShutdown = new \React\Promise\Deferred();

        $deferredShutdown->promise()->then(function () use (&$conns) {
            for ($i = 0; $i < 5; $i++) $conns[$i]->close();
        });

        for ($i = 0; $i < 5; $i++) {
            $conns[$i] = $conn = new \Thruway\Connection($this->_connOptions, $loop);
            $conn->on('open', function (\Thruway\ClientSession $session) use ($i, &$sessionIds, &$subscribePromises, $deferredShutdown) {
                $sessionIds[$i] = $session->getSessionId();
                $subscribePromises[] = $session->subscribe('test.whitelist', function ($args) use ($i, $deferredShutdown) {
                    $this->_testResult .= "-" . $args[0] . "." . $i . "-";
                    if ($args[0] == "F") {
                        $deferredShutdown->resolve();
                    }
                });
            });

            $conn->open(false);
        }

        $this->_conn->on('open', function (\Thruway\ClientSession $session) use ($subscribePromises, &$sessionIds) {
            \React\Promise\all($subscribePromises)->then(function () use ($session, &$sessionIds) {
                $session->publish('test.whitelist', ["A"], null,
                    ['acknowledge' => true]
                )->then(function () use ($session, &$sessionIds) {
                    $session->publish('test.whitelist', ["B"], null, [
                        'acknowledge' => true,
                        'eligible'    => $sessionIds
                    ])->then(function () use ($session, &$sessionIds) {
                        $session->publish('test.whitelist', ["C"], null, [
                            'acknowledge' => true,
                            'exclude'     => [$sessionIds[1]],
                            'eligible'    => $sessionIds
                        ])->then(function () use ($session, &$sessionIds) {
                            $session->publish('test.whitelist', ["D"], null, [
                                'acknowledge' => true,
                                'exclude'     => [$sessionIds[1]],
                                'eligible'    => [$sessionIds[2]]
                            ])->then(function () use ($session, &$sessionIds) {
                                $session->publish('test.whitelist', ["E"], null, [
                                    'acknowledge' => true,
                                    'exclude'     => [$sessionIds[1]],
                                    'eligible'    => []
                                ])->then(function () use ($session, &$sessionIds) {
                                    $session->publish('test.whitelist', ["F"], null, [
                                        'acknowledge' => true,
                                        'exclude'     => [],
                                        'eligible'    => [$sessionIds[0]]
                                    ])->then(function () use ($session) {
                                        $session->close();
                                    });;
                                });
                            });
                        });
                    });
                });
            });
        });

        $this->_conn->open();

        $this->assertEquals("-A.0--A.1--A.2--A.3--A.4--B.0--B.1--B.2--B.3--B.4--C.0--C.2--C.3--C.4--D.2--F.0-", $this->_testResult);
    }
}