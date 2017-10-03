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
        $subscriptionUri = $this->fixupUri($subscriptionUri);
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
        $uri = $this->fixupUri($uri);

        // if the uri is empty - then match everything
        if ($uri == "") return true;

        // if there is a trailing . then remove it and run it through the
        // regular validator
        if (substr($uri, strlen($uri) - 1) === '.') $uri = substr($uri, 0, strlen($uri) - 1);

        // allow matches to a normal URI or one with a trailing dot
        return Utils::uriIsValid($uri) || Utils::uriIsValid($uri . ".");
    }

    /**
     * @param $uri
     * @return string
     */
    private function fixupUri($uri) {
        // a single "." matches everything
        if ($uri === '.') return '';

        return $uri;
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
