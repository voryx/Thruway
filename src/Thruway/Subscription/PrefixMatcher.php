<?php


namespace Thruway\Subscription;


use Thruway\Common\Utils;

/**
 * Class PrefixMatcher
 * @package Thruway\Subscription
 */
class PrefixMatcher implements MatcherInterface
{
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

    /**
     * @param $parentUri
     * @param $parentOptions
     * @param $childUri
     * @param $childOptions
     * @return mixed
     */
    public function isSubGroup($parentUri, $parentOptions, $childUri, $childOptions)
    {
        return $this->matches($childUri, $parentUri, $parentOptions);
    }

}
