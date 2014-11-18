<?php


class TopicStateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Thruway\Connection
     */
    protected $_conn;
    protected $_error;
    protected $_testArgs;


    public function setUp()
    {
        $this->_testArgs = null;
        $this->_error    = null;

        $options = [
            "realm"       => 'topic.state.test.realm',
            "url"         => 'ws://127.0.0.1:8090',
            "max_retries" => 0,
        ];

        $this->_conn = new \Thruway\Connection($options);
    }

    public function testSubscribe()
    {

        $this->_conn->on('open', function (\Thruway\ClientSession $session) {

                /**
                 * Subscribe to event that can provide a state
                 */
                $session->subscribe('test.state.topic',
                    function ($args) use ($session) {

                        $this->_testArgs = $args;
                        $session->close();
                    }
                )->then(
                    function () {
                        //Everything is okay
                    },
                    function () use ($session) {
                        $session->close();
                        throw new Exception("subscribe failed.");
                    });
            }
        );

        $this->_conn->open();

        $this->assertEquals('testing', $this->_testArgs[0]);

    }
}