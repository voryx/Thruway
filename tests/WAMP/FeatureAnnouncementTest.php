<?php


class FeatureAnnouncementTest extends PHPUnit_Framework_TestCase
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
                "realm" => 'testRealm',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
            )
        );
    }

    public function testCheckHelloDetails()
    {
        $this->_error = null;
        $this->_testResult = null;

        $roleInfo = null;

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) use (&$roleInfo) {
                $roleInfo = $this->_conn->getClient()->getRoleInfoObject();

                $session->call('com.example.get_hello_details')->then(
                    function ($r) use ($session) {
                        $this->_testResult = $r;

                        $session->close();
                    },
                    function ($r) use ($session) {
                        $this->_error = $r;
                        $session->close();
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals($roleInfo, $this->_testResult[0]);
    }
}