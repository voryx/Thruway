<?php


use Thruway\ClientSession;

class QueryParamAuthTest extends PHPUnit_Framework_TestCase
{

    private $_error;
    private $_result;
    private $_authid;
    private $_user;
    private $_password;

    public function setUp()
    {
        $this->_error    = null;
        $this->_result   = null;
        $this->_user     = null;
        $this->_password = null;
    }


    /**
     * @param $token
     * @return array
     */
    private function getOptions($token)
    {
        return [
          "realm"       => 'query_param_auth_realm',
          "url"         => "ws://127.0.0.1:8090/?token={$token}",
          "max_retries" => 0,
          "onChallenge" => function () {
          },
          "authmethods" => ["query_param_auth"]
        ];
    }


    public function testLogin()
    {


        $conn = new \Thruway\Connection($this->getOptions("sadfsaf"));

        $conn->on('open', function (ClientSession $session, $transport, $details) {
            $session->close();
            $this->_result = "logged in";
            $this->_authid = $details->authid;
        });

        $conn->on('error', function ($reason) {
            $this->_error = $reason;
        });

        $conn->open();

        $this->assertNull($this->_error);
        $this->assertEquals("logged in", $this->_result);
        $this->assertEquals("joe", $this->_authid);

    }

    public function testBadToken()
    {


        $conn = new \Thruway\Connection($this->getOptions("asdasDAaSDasdaSD"));

        $conn->on('open', function (ClientSession $session) {

            $this->_result = "logged in";
            $session->close();
        });

        $conn->on('error', function ($reason) {
            $this->_error = $reason;
        });

        $conn->open();

        $this->assertEquals("thruway.error.authentication_failure", $this->_error);
        $this->assertNull($this->_result);
    }


}