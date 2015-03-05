<?php


namespace Thruway\Message\Traits;


use Thruway\Message\SubscribeMessage;

/**
 * Class OptionsMatchTypeTrait
 * @package Thruway\Message\Traits
 */
trait OptionsMatchTypeTrait
{
    /**
     * @return string
     */
    public function getMatchType()
    {
        return SubscribeMessage::getMatchTypeFromOption($this->getOptions());
    }

    /**
     * @param string $matchType
     */
    public function setMatchType($matchType)
    {
        $options = $this->getOptions();
        if (is_object($options)) {
            $options->match = $matchType;
            if ($matchType == "exact") {
                unset($options->match);
            }
        }
        $this->setOptions($options);
    }
} 