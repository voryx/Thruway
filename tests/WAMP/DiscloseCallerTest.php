<?php


class DiscloseCallerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Thruway\Connection
     */
    protected $_conn;
    protected $_error;
    protected $_testResult;
    protected $_testCallerId;
    protected $_testAuthId;
    protected $_testAuthMethod;
    protected $_testAuthRole;


    public function setUp()
    {
        $this->_testResult = null;
        $this->_error = null;
        $this->_testCallerId = null;
        $this->_testAuthId = null;
        $this->_testAuthMethod = null;
        $this->_testAuthRole = null;

        $challenge = function ($session, $method) {
            return "letMeIn";
        };

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'testSimpleAuthRealm',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["simplysimple"],
                "onChallenge" => $challenge
            )
        );
    }

    public function testCall()
    {

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                $add2 = function ($args, $kwargs, $details) {
                    $this->_testCallerId = $details->caller;
                    $this->_testAuthId = $details->authid;
                    $this->_testAuthMethod = $details->authmethod;
//                    $this->_testAuthRole = $details["authrole"];

                    return $args[0] + $args[1];
                };

                $session->register('com.example.disclosecallertest', $add2, ['disclose_caller' => true])->then(
                    function () use ($session) {
                        $session->call('com.example.disclosecallertest', [1, 2])->then(
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


            }
        );

        $this->_conn->getClient()->setAuthId("me@example.com");

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals(3, $this->_testResult[0]);
        $this->assertNotEmpty($this->_testCallerId);
        $this->assertEquals("me@example.com", $this->_testAuthId);
        $this->assertEquals("simplysimple", $this->_testAuthMethod);
    }
} 