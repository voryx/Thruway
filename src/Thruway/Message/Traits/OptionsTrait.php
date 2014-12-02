<?php

namespace Thruway\Message\Traits;

/**
 * Class OptionsTrait
 * @package Thruway\Message
 */
trait OptionsTrait
{

    /**
     * @var \stdClass
     */
    private $options;

    /**
     * Get options
     * 
     * @return \stdClass
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set options
     * 
     * @param \stdClass|array $options
     */
    public function setOptions($options)
    {
        $this->options = (object)$options;
    }

}