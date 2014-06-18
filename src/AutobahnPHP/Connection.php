<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/17/14
 * Time: 12:12 AM
 */

namespace AutobahnPHP;


use AutobahnPHP\Peer\Client;
use AutobahnPHP\Role\Callee;
use AutobahnPHP\Role\Caller;
use AutobahnPHP\Role\Publisher;
use AutobahnPHP\Role\Subscriber;
use AutobahnPHP\Transport\WebsocketClient;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\Socket\ConnectionInterface;

/**
 * Class Connection
 * @package AutobahnPHP
 */
class Connection implements EventEmitterInterface

{
    use EventEmitterTrait;

    /**
     * @var Transport\WebsocketClient
     */
    private $transport;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Caller
     */
    private $caller;
    /**
     * @var Callee
     */
    private $callee;
    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Array
     */
    private $options;

    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @param array $options
     */
    function __construct(Array $options)
    {

        $this->options = $options;

        $url = isset($options['url']) ? $options['url'] : null;

        $challenge = isset($this->options['onChallenge']) ? $this->options['onChallenge'] : null;

        //Peer
        $this->client = new Client($challenge);

//        if (isset($this->options['onChallenge'])) {
//            $this->client->on('challenge', $this->options['onChallenge']);
//        }


        $this->transport = new WebsocketClient($url, $this->client);

        if (isset($options['onClose'])) {
            $this->transport->on('close', $options['onClose']);
        }

        $this->transport->on('connect', array($this, 'onConnect'));

//        $this->transport->startTransport();
    }


    /**
     * @param ClientSession $session
     */
    public function onConnect(ClientSession $session)
    {

        echo "Connected";

        $this->client->on(
            'open',
            function ($session) {
                $this->emit('open', [$session]);
            }
        );

        //Roles
        $this->callee = new Callee($session);
        $this->caller = new Caller($session);
        $this->subscriber = new Subscriber($session);
        $this->publisher = new Publisher($session);

        $this->client
            ->addRole($this->callee)
            ->addRole($this->caller)
            ->addRole($this->subscriber)
            ->addRole($this->publisher);

        $this->transport->setPeer($this->client);

        $session->setRealm($this->getOptions()['realm']);
        $this->client->startSession($session);

    }

    /**
     *
     */
    public function onClose()
    {
        //TODO: destroy it all!!

    }

    /**
     *
     */
    public function open()
    {
        $this->transport->startTransport();
    }


    /**** Setters and Getters ****/
    /**
     * @return mixed
     */
    public function getCallee()
    {
        return $this->callee;
    }

    /**
     * @return mixed
     */
    public function getCaller()
    {
        return $this->caller;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @return mixed
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }


    /**
     * @return mixed
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    /**
     * @return WebsocketClient
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return ClientSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param ClientSession $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

}