<?php


class DisclosePublisherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Thruway\Connection
     */
    protected $_conn;
    protected $_error;
    protected $_testResult;
    protected $_testPublisherId;
    protected $_testAuthId;
    protected $_testAuthMethod;
    protected $_testAuthRole;
    protected $_testTopic;


    public function setUp()
    {
        $this->_testResult      = null;
        $this->_error           = null;
        $this->_testPublisherId = null;
        $this->_testAuthId      = null;
        $this->_testAuthMethod  = null;
        $this->_testAuthRole    = null;
        $this->_testTopic       = null;

        $challenge = function ($session, $method) {
            return "letMeIn";
        };

        $options = [
          "realm"       => 'testSimpleAuthRealm',
          "url"         => 'ws://127.0.0.1:8090',
          "max_retries" => 0,
          "authmethods" => ["simplysimple"],
          "onChallenge" => $challenge
        ];

        $this->_conn = new \Thruway\Connection($options);
    }

    public function testSubscribe()
    {


        $this->_conn->on('open', function (\Thruway\ClientSession $session) {

            /**
             * Subscribe to event
             */
            $session->subscribe('com.example.publish',
              function ($args, $kwargs = null, $details, $publicationId = null) {

                  $this->_testArgs        = $args;
                  $this->_testPublisherId = $details->publisher;
                  $this->_testTopic       = $details->topic;
                  $this->_testAuthId      = $details->authid;
                  $this->_testAuthMethod  = $details->authmethod;
                  $this->_testAuthRole    = $details->authroles;

              },
              ['disclose_publisher' => true]
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

        $this->assertNull($this->_error, "Got this error when receiving Event: {$this->_error}");
        $this->assertEquals('ok', $this->_testResult[0]);
        $this->assertNotEmpty($this->_testPublisherId);
        $this->assertEquals("anonymous", $this->_testAuthId);
        $this->assertEquals("internalClient", $this->_testAuthMethod);
        $this->assertEquals('com.example.publish', $this->_testTopic);
    }
} 