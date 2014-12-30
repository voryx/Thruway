<?php


namespace Thruway\Subscription;


use Thruway\Common\Utils;

class PrefixMatcher implements MatcherInterface {
    /**
     * @return array
     */
    public function getMatchTypes()
    {
        return ["prefix"];
    }

    /**
     * @param $uri
     * @param $options
     * @return mixed
     */
    public function getMatchHash($uri, $options)
    {
        return "prefix_" . $uri;
    }

    /**
     * @param $eventUri
     * @param $subscriptionUri
     * @param $subscriptionOptions
     * @return bool
     */
    public function matches($eventUri, $subscriptionUri, $subscriptionOptions)
    {
        $matchingPart = substr($eventUri, 0, strlen($subscriptionUri));

        return $matchingPart == $subscriptionUri;
    }

    /**
     * @param $uri
     * @param $options
     * @return bool
     */
    public function uriIsValid($uri, $options)
    {
        // allow matches to a normal URI or one with a trailing dot
        return Utils::uriIsValid($uri) || Utils::uriIsValid($uri . ".");
    }
}