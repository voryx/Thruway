<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/17/14
 * Time: 12:12 AM
 */

namespace Thruway;


use React\EventLoop\LoopInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;
use Thruway\Transport\TransportInterface;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;


/**
 * Class Connection
 * @package Thruway
 */
class Connection implements EventEmitterInterface

{
    use EventEmitterTrait;


    /**
     * @var Client
     */
    private $client;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options
     * @param LoopInterface $loop
     * @throws \Exception
     */
    function __construct(Array $options, LoopInterface $loop = null)
    {

        $this->options = $options;

        $this->client = new Client($options['realm'], $loop);

        /*
         * Add the transport provider
         * TODO: Allow for multiple transport providers
         */
        $url = isset($options['url']) ? $options['url'] : null;
        $this->client->addTransportProvider(new PawlTransportProvider($url));

        $this->client->setReconnectOptions($options);

        /*
         * Authentication on challenge callback
         */
        if (isset($options['onChallenge']) && is_callable($options['onChallenge'])
            && isset($options['authmethods'])
            && is_array($options['authmethods'])
        ) {
            $this->client->setAuthMethods($options['authmethods']);
            $this->client->on(
                'challenge',
                function (ClientSession $session, ChallengeMessage $msg) use ($options) {
                    $token = $options['onChallenge']($session, $msg->getAuthMethod());
                    $session->sendMessage(new AuthenticateMessage($token));
                }
            );
        }

        /*
         * Handle On Open event
         *
         */
        $this->client->on(
            'open',
            function (ClientSession $session, TransportInterface $transport) {
                $this->transport = $transport;
                $this->emit('open', [$session]);
            }
        );

        /*
         * Handle On Close event
         */
        $this->client->on(
            'close',
            function ($reason) {
                if (isset($this->options['onClose']) && is_callable($this->options['onClose'])) {
                    $this->options['onClose']($reason);
                }
            }
        );
    }

    /**
     *  Process events at a set interval
     *
     * @param int $timer
     */
    public function doEvents($timer = 1)
    {
        $loop = $this->getClient()->getLoop();

        $looping = true;
        $loop->addTimer(
            $timer,
            function () use (&$looping) {
                $looping = false;
            }
        );

        while ($looping) {
            usleep(1000);
            $loop->tick();
        }
    }

    /**
     *  Starts the open sequence
     */
    public function open()
    {
        $this->client->start();
    }

    /**
     * Starts the close sequence
     */
    public function close()
    {
        $this->client->setAttemptRetry(false);
        $this->transport->close();
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }


}