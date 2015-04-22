<?php


use Thruway\ClientSession;
use Thruway\Message\ChallengeMessage;

class WampCraTest extends PHPUnit_Framework_TestCase
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
     * OnChallenge Callback
     *
     * @param ClientSession $session
     * @param $method
     * @param ChallengeMessage $msg
     * @return bool|mixed
     */
    public function onChallenge(ClientSession $session, $method, ChallengeMessage $msg)
    {
        if ("wampcra" !== $method) {
            return false;
        }
        $cra = new \Thruway\Authentication\ClientWampCraAuthenticator($this->_user, $this->_password);
        return $cra->getAuthenticateFromChallenge($msg)->getSignature();
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        return [
            "realm"       => 'test.wampcra.auth',
            "url"         => 'ws://127.0.0.1:8090',
            "max_retries" => 0,
            "onChallenge" => [$this, "onChallenge"],
            "authid"      => $this->_user,
            "authmethods" => ["wampcra"]
        ];
    }


    public function testLogin()
    {

        $this->_user     = "joe";
        $this->_password = "secret2";

        $conn = new \Thruway\Connection($this->getOptions());

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

    public function testBadPassword()
    {
        $this->_user     = "joe";
        $this->_password = "badpassword";

        $conn = new \Thruway\Connection($this->getOptions());

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

    public function testBadUser()
    {
        $this->_user     = "joe123";
        $this->_password = "secret2";

        $conn = new \Thruway\Connection($this->getOptions());

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