<?php

namespace Thruway\Subscription;
use Thruway\Common\Utils;


/**
 * Interface MatcherInterface
 * @package Thruway\Subscription
 */
interface MatcherInterface {
    /**
     * @return array
     */
    public function getMatchTypes();

    /**
     * @param $uri
     * @param $options
     * @return mixed
     */
    public function getMatchHash($uri, $options);

    /**
     * @param $eventUri
     * @param $subscriptionUri
     * @param $subscriptionOptions
     * @return bool
     */
    public function matches($eventUri, $subscriptionUri, $subscriptionOptions);

    /**
     * @param $uri
     * @param $options
     * @return bool
     */
    public function uriIsValid($uri, $options);
} 