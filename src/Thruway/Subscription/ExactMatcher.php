<?php

namespace Thruway\Subscription;

use Thruway\Common\Utils;

class ExactMatcher implements MatcherInterface {
    public function getMatchTypes()
    {
        return ["exact"];
    }

    public function getMatchHash($uri, $options) {
        return "exact_" . $uri;
    }

    public function matches($eventUri, $subscriptionUri, $subscriptionOptions) {
        return $eventUri == $subscriptionUri;
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
        return $parentUri == $childUri;
    }


}