<?php

namespace Thruway\Subscription;

use Thruway\Common\Utils;

/**
 * Class ExactMatcher
 * @package Thruway\Subscription
 */
class ExactMatcher implements MatcherInterface
{
    /**
     * @return array
     */
    public function getMatchTypes()
    {
        return ['exact'];
    }

    /**
     * @param $uri
     * @param $options
     * @return string
     */
    public function getMatchHash($uri, $options)
    {
        return 'exact_' . $uri;
    }

    /**
     * @param $eventUri
     * @param $subscriptionUri
     * @param $subscriptionOptions
     * @return bool
     */
    public function matches($eventUri, $subscriptionUri, $subscriptionOptions)
    {
        return $eventUri === $subscriptionUri;
    }

    /**
     * @param $uri
     * @param $options
     * @return bool
     */
    public function uriIsValid($uri, $options)
    {
        return Utils::uriIsValid($uri);
    }

    /**
     * @param $parentUri
     * @param $parentOptions
     * @param $childUri
     * @param $childOptions
     * @return mixed
     */
    public function isSubGroup($parentUri, $parentOptions, $childUri, $childOptions)
    {
        return $parentUri === $childUri;
    }

}
