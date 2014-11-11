<?php

namespace Thruway\Message\Traits;

/**
 * Class OptionsTrait
 * @package Thruway\Message
 */
trait OptionsTrait
{

    /**
     * @var mixed
     */
    private $options;

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = (object)$options;
    }

}