<?php

namespace Thruway;



use React\EventLoop\LoopInterface;
use Thruway\Message\AuthenticateMessage;
use Thruway\Message\ChallengeMessage;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;
use Thruway\Transport\TransportInterface;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;


/**
 * Class Connection
 *
 * @package Thruway
 */
class Connection implements EventEmitterInterface
{

    /**
     * Using \Evenement\EventEmitterTrait to implements \Evenement\EventEmitterInterface
     * @see \Evenement\EventEmitterTrait
     */
    use EventEmitterTrait;

    /**
     * @var \Thruway\Peer\Client
     */
    private $client;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    private $transport;

    /**
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param array $options
     * @param \React\EventLoop\LoopInterface $loop
     * @throws \Exception
     */
    public function __construct(Array $options, LoopInterface $loop = null)
    {

        $this->options = $options;
        $this->client  = new Client($options['realm'], $loop);
        $url           = isset($options['url']) ? $options['url'] : null;
        $pawlTransport = new PawlTransportProvider($url);
        $this->client->addTransportProvider($pawlTransport);
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

        if (isset($this->options['onClose']) && is_callable($this->options['onClose'])) {
            $this->on('close', $this->options['onClose']);
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
                $this->emit('close', [$reason]);
            }
        );

        $this->client->on('error', function ($reason) {
            $this->emit('error', [$reason]);

        });
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
     * @param bool $startLoop
     */
    public function open($startLoop = true)
    {
        $this->client->start($startLoop);
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