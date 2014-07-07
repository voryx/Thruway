<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/17/14
 * Time: 12:12 AM
 */

namespace Thruway;


use React\EventLoop\LoopInterface;
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
         * Authentication
         */
        if (isset($options['onChallenge']) && is_callable($options['onChallenge'])
            && isset($options['authmethods'])
            && is_array($options['authmethods'])
        ) {
            foreach ($options['authmethods'] as $authmethod) {
                $this->client->addAuthMethod([$authmethod => ["callback" => $options['onChallenge']]]);
            }
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

}