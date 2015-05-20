<?php


namespace Voryx\ThruwayBundle;


class Details
{
    protected $args;
    protected $argsKw;
    protected $details;

    function __construct($args = [], $argsKw = null, $details = null)
    {
        $this->args    = $args;
        $this->argsKw  = $argsKw;
        $this->details = $details;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return mixed
     */
    public function getArgsKw()
    {
        return $this->argsKw;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }


}