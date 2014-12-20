<?php

namespace Thruway\Topic;


use React\EventLoop\LoopInterface;
use Thruway\Logging\Logger;
use Thruway\Module\Module;
use Thruway\Peer\Client;
use Thruway\Realm;
use Thruway\Subscription;

/**
 * Class TopicStateManager
 * @package Thruway
 */
class TopicStateManager extends Module implements TopicStateManagerInterface
{

    /**
     * @var boolean
     */
    private $ready;

    /**
     * @var TopicManager
     */
    private $topicManger;

    /**
     * @param string $realm
     * @param LoopInterface $loop
     */
    function __construct($realm, LoopInterface $loop = null)
    {

        $this->ready = false;

        parent::__construct($realm, $loop);
    }

    /**
     * Gets called when the module is initialized in the router
     */
    public function onInitialize()
    {
        $topicStateRealm   = new Realm($this->getRealm());
        $topicStateRealm->setTopicStateManager($this);
        $this->router->getRealmManager()->addRealm($topicStateRealm);
    }

    /**
     * Handles session started
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportProviderInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $promises   = [];
        $promises[] = $this->getCallee()->register($session, 'add_topic_handler', [$this, "addTopicHandler"]);
        $promises[] = $this->getCallee()->register($session, 'remove_topic_handler', [$this, "removeTopicHandler"]);

        $pAll = \React\Promise\all($promises);

        $pAll->then(
            function () {
                $this->setReady(true);
            },
            function () {
                $this->setReady(false);
            }
        );
    }

    /**
     * @param $args
     * @throws \Exception
     */
    public function addTopicHandler($args)
    {
        $topicName    = isset($args[0]->topic) ? $args[0]->topic : null;
        $handlerUri   = isset($args[0]->handler_uri) ? $args[0]->handler_uri : null;
        $topicManager = $this->getTopicManger();
        $topic        = $topicManager->getTopic($topicName, true);

        $topic->setStateHandler($handlerUri);

    }

    /**
     * @param $args
     */
    public function removeTopicHandler($args)
    {
        $topicName    = array_shift($args);
        $topicManager = $this->getTopicManger();
        $topic        = $topicManager->getTopic($topicName);

        if ($topic) {
            $topic->removeStateHandler();
        }
    }


    /**
     * Gets and published the topics state to this subscription
     *
     * @param Subscription $subscription
     * @return mixed
     */
    public function publishState(Subscription $subscription)
    {
        //Pause all non-state building event messages
        $subscription->pauseForState();

        $topic     = $this->getTopicManger()->getTopic($subscription->getTopic());
        $sessionId = $subscription->getSession()->getSessionId();

        $this->getCaller()->call($this->getSession(), $topic->getStateHandler(), [$topic->getUri(), $sessionId])->then(
            function ($res) use ($subscription) {
                $pubId = null;
                if (isset($res[0])) $pubId = $res[0];
                $subscription->unPauseForState($pubId);
            },
            function ($error) use ($topic, $subscription) {
                Logger::error($this, "Could not call '{$topic->getStateHandler()}'");
                $subscription->unPauseForState();
            }
        );

    }

    /**
     * Set ready flag
     *
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * Get ready flag
     *
     * @return boolean
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * @param TopicManager $topicManager
     * @return mixed
     */
    public function setTopicManager(TopicManager $topicManager)
    {
        $this->topicManger = $topicManager;
    }

    /**
     * @return TopicManager
     */
    public function getTopicManger()
    {
        return $this->topicManger;
    }

}
