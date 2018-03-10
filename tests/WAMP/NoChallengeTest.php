<?php

class AutoAuthProvider extends \Thruway\Authentication\AbstractAuthProviderClient
{
    /**
     * @return mixed
     */
    public function getMethodName()
    {
        return 'auto_auth';
    }

    public function processHello(array $args)
    {
        return ['NOCHALLENGE', (object)['authid' => 'theauth', '_thruway_authextra' => ['from' => 'hello', 'one' => 1, 'two' => [1, 2]]]];
    }
}

class NoChallengeTest extends PHPUnit_Framework_TestCase
{
    /*
     * This is a complex test - should do this a better way
     */
    public function testAuthDetails()
    {
        $this->_result     = null;
        $this->_error      = null;
        $this->_resultPS   = null;
        $this->_errorPS    = null;
        $this->_challenged = false;

        $loop   = \React\EventLoop\Factory::create();
        $router = new \Thruway\Peer\Router($loop);

        $router->registerModule(new \Thruway\Authentication\AuthenticationManager());

        $authClient = new AutoAuthProvider(['my_realm'], $loop);

        $router->addInternalClient($authClient);

        $client = new \Thruway\Peer\Client('my_realm', $loop);

        $client->setAuthMethods(['auto_auth']);
        $client->setAttemptRetry(false);

        $callCount        = 0;
        $stopOnSecondCall = function () use ($loop, &$callCount) {
            $callCount++;
            if ($callCount == 2) {
                $loop->stop();
            }
        };

        $client->on('open', function (\Thruway\ClientSession $session) use ($stopOnSecondCall) {
            // RPC stuff
            $session->register('get_the_authextra', function ($args, $argskw, $details) {
                return [$details];
            }, ['disclose_caller' => true])->then(function () use ($session, $stopOnSecondCall) {
                $session->call('get_the_authextra')->then(function ($args) use ($stopOnSecondCall) {
                    $this->_result = $args;

                    $stopOnSecondCall();
                }, function ($err) use ($stopOnSecondCall) {
                    $this->_error = "call failed";
                    $stopOnSecondCall();
                });
            }, function () use ($stopOnSecondCall) {
                $this->_error = "registration failed";
                $stopOnSecondCall();
            });

            // PubSub
            $session->subscribe('test_sub', function ($args, $argskw, $details) use ($stopOnSecondCall) {
                $this->_resultPS = $details;
                $stopOnSecondCall();
            }, ["disclose_publisher" => true])->then(function () use ($session, $stopOnSecondCall) {
                $session->publish('test_sub', [], null, ["exclude_me" => false, "acknowledge" => true])->then(function () use ($stopOnSecondCall) {

                }, function () use ($stopOnSecondCall) {
                    $this->_errorPS = "Error publishing";
                    $stopOnSecondCall();
                });
            }, function () use ($stopOnSecondCall) {
                $this->_errorPS = "Error subscribing";
                $stopOnSecondCall();
            });
        });

        $router->addTransportProvider(new \Thruway\Transport\RatchetTransportProvider('127.0.0.1', 58089));

        $client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider('ws://127.0.0.1:58089/'));

        $loop->addTimer(5, function () use ($loop) {
            $loop->stop();
        });

        $loop->addTimer(0.1, function () use ($client) {
            $client->start(false);
        });

        $router->start();

        $this->assertNull($this->_error, $this->_error);
        $this->assertNull($this->_errorPS, $this->_errorPS);

        $this->assertNotNull($this->_result);
        $args = $this->_result;
        $this->assertArrayHasKey(0, $args);
        $this->assertTrue(is_object($args[0]));
        $this->assertObjectHasAttribute('_thruway_authextra', $args[0]);

        $this->assertObjectHasAttribute('authid', $args[0]);
        $this->assertEquals('theauth', $args[0]->authid);

        $this->assertObjectHasAttribute('authmethod', $args[0]);
        $this->assertEquals('auto_auth', $args[0]->authmethod);

        $this->assertObjectHasAttribute('authrole', $args[0]);
        $this->assertEquals('authenticated_user', $args[0]->authrole);

        $this->assertObjectHasAttribute('authroles', $args[0]);
        $this->assertEquals('authenticated_user', $args[0]->authroles[0]);

        $this->assertNotNull($this->_resultPS);
        $this->assertTrue(is_object($this->_resultPS));
        $this->assertObjectHasAttribute('_thruway_authextra', $this->_resultPS);
        $this->assertEquals('hello', $this->_resultPS->_thruway_authextra->from);
    }
}