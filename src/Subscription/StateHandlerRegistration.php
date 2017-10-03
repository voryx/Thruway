<?php

namespace Thruway\Subscription;

use Thruway\ClientSession;
use Thruway\Logging\Logger;
use Thruway\Message\Traits\OptionsMatchTypeTrait;
use Thruway\Message\Traits\OptionsTrait;

class StateHandlerRegistration
{
    use OptionsTrait;
    use OptionsMatchTypeTrait;

    /**
     * @var ClientSession
     */
    protected $clientSession;

    /**
     * @var string
     */
    protected $procedureName;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    public function __construct($clientSession, $procedureName, $uri, $options, MatcherInterface $matcher)
    {
        $this->setClientSession($clientSession);
        $this->setProcedureName($procedureName);
        $this->setUri($uri);
        $this->setOptions($options);
        $this->setMatcher($matcher);
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

        $sessionId = $subscription->getSession()->getSessionId();

        $this->clientSession->call($this->getProcedureName(),
            [$subscription->getUri(), $sessionId, $subscription->getOptions(), $subscription->getSession()->getAuthenticationDetails()])->then(
            function ($res) use ($subscription) {
                $pubId = null;
                if (isset($res[0])) {
                    $pubId = $res[0];
                }
                $subscription->unPauseForState($pubId);
            },
            function ($error) use ($subscription) {
                Logger::error($this, "Could not call '{$this->getProcedureName()}'");
                $subscription->unPauseForState();
            }
        );

    }

    /**
     * @return ClientSession
     */
    public function getClientSession()
    {
        return $this->clientSession;
    }

    /**
     * @param ClientSession $clientSession
     */
    public function setClientSession($clientSession)
    {
        $this->clientSession = $clientSession;
    }

    /**
     * @return string
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @param string $procedureName
     */
    public function setProcedureName($procedureName)
    {
        $this->procedureName = $procedureName;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return MatcherInterface
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @param MatcherInterface $matcher
     */
    public function setMatcher($matcher)
    {
        $this->matcher = $matcher;
    }

    public function handlesStateFor(SubscriptionGroup $subscriptionGroup)
    {
        if ($subscriptionGroup->getMatchType() == $this->getMatchType()
            || $subscriptionGroup->getMatchType() === 'exact') {
            $sgUri     = $subscriptionGroup->getUri();
            $sgOptions = $subscriptionGroup->getOptions();

            return $this->matcher->isSubGroup($this->getUri(), $this->getOptions(), $sgUri, $sgOptions);
        }

        return false;
    }
}
