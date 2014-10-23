<?php


class AuthorizingRealmTest extends PHPUnit_Framework_TestCase {
    /**
     * @var \Thruway\Connection
     */
    private $_conn;

    private $_testResult;
    private $_error;

    public function setup() {

    }

    public function testNotAuthorized() {
        $this->_conn = new \Thruway\Connection([
            "realm" => 'authorizing_realm',
            "url" => 'ws://127.0.0.1:8090',
            "max_retries" => 0
        ]);

        $this->_testResult = "";
        $this->_error = null;
        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
            $promises = [];

            $promises[] = $session->register('something', function () {})->then(
                function () {
                    $this->_error = "registration should have failed";
                },
                function ($res) {
                    $this->assertTrue($res instanceof \Thruway\Message\ErrorMessage);
                    $this->assertEquals("wamp.error.not_authorized", $res->getErrorUri());
                    $this->_testResult .= "ok";
                }
            );

            $promises[] = $session->call('some_rpc', [])->then(
                function () {
                    $this->_error = "call should have failed";
                },
                function ($res) {
                    $this->assertTrue($res instanceof \Thruway\Message\ErrorMessage);
                    $this->assertEquals("wamp.error.not_authorized", $res->getErrorUri());
                    $this->_testResult .= "ok";
                }
            );

            $promises[] = $session->subscribe('some_topic', function () {})->then(
                function () {
                    $this->_error = "subscribe should have failed";
                },
                function ($res) {
                    $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $res);
                    $this->assertEquals("wamp.error.not_authorized", $res->getErrorUri());
                    $this->_testResult .= "ok";
                }
            );

            $promises[] = $session->publish('some_topic', ["Hello"], null, ["acknowledge" => true])->then(
                function () {
                    $this->_error = "publish should have failed";
                },
                function ($res) {
                    $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $res);
                    $this->assertEquals("wamp.error.not_authorized", $res->getErrorUri());
                    $this->_testResult .= "ok";
                }
            );

            React\Promise\all($promises)->then(
                function () {
                    $this->_conn->close();
                },
                function () {
                    $this->_conn->close();
                }
            );
        });

        $this->_conn->open();

        $this->assertNull($this->_error, "Error: " . $this->_error);
        $this->assertEquals("okokokok", $this->_testResult);
    }

    private function flushRules() {
        $conn = new \Thruway\Connection([
            "realm" => 'authful_realm',
            "url" => 'ws://127.0.0.1:8090',
            "max_retries" => 0,
            "authmethods" => ["simplysimple"],
            "onChallenge" => function () { return "ozTheGreatAndPowerful"; }
        ]);

        // now set permissions to allow stuff
        $conn->on('open', function (\Thruway\ClientSession $session) use ($conn) {
            $session->call("flush_authorization_rules", [false])->then(
                function ($r) use ($conn) {
                    $conn->close();
                },
                function ($msg) use ($conn) {
                    $conn->close();
                    $this->fail("failed to flush rules " . $msg->getErrorUri());
                }
            );
        });

        $conn->open();
    }

    public function testAuthorizedActions() {
        $this->flushRules();

        $challenge = function ($session, $method) {
            return "letMeIn";
        };

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'authful_realm',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["simplysimple"],
                "onChallenge" => $challenge
            )
        );

        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
            $session->call("add_authorization_rule", [[
                "role" => "sales",
                "action" => "publish",
                "uri" => "sales.numbers",
                "allow" => true
            ]])->then(
                function ($r) {
                    $this->_conn->close();
                    $this->_testResult = "ok";
                },
                function ($msg) {
                    $this->_conn->close();
                    $this->_testResult = "failed";
                    $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $msg);
                    $this->assertEquals("wamp.error.not_authorized", $msg->getErrorUri());
                }
            );
        });

        $this->_conn->open();

        $this->assertEquals("failed", $this->_testResult);

        $this->_testResult = "";
        $this->_error = null;

        $this->_conn = new \Thruway\Connection([
            "realm" => 'authful_realm',
            "url" => 'ws://127.0.0.1:8090',
            "max_retries" => 0,
            "authmethods" => ["simplysimple"],
            "onChallenge" => function () { return "ozTheGreatAndPowerful"; }
        ]);

        // now set permissions to allow stuff
        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
            $session->call("add_authorization_rule", [[
                "role" => "sales",
                "action" => "call",
                "uri" => "add_authorization_rule",
                "allow" => true
            ]])->then(
                function ($r) {
                    $this->_conn->close();
                    $this->_testResult = "ok";
                    $this->assertEquals("ADDED", $r);
                },
                function ($msg) {
                    $this->_conn->close();
                    $this->_error = "error adding authorization rule";
                }
            );
        });

        $this->_conn->open();

        $this->assertNull($this->_error, "Error: " . $this->_error);
        $this->assertEquals("ok", $this->_testResult);

        // now try to use the thing

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'authful_realm',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["simplysimple"],
                "onChallenge" => function () { return "letMeIn"; }
            )
        );

        $this->_testResult = null;

        $this->_conn->on('open', function (\Thruway\ClientSession $session) {
            $session->call("add_authorization_rule", [[
                "role" => "sales",
                "action" => "publish",
                "uri" => "sales.numbers",
                "allow" => true
            ]])->then(
                function ($r) {
                    $this->_conn->close();
                    $this->_testResult = "success";
                },
                function ($msg) {
                    $this->_conn->close();
                    $this->_testResult = "failed";
                }
            );
        });

        $this->_conn->open();

        $this->assertEquals("success", $this->_testResult);

        $this->flushRules();
    }
} 