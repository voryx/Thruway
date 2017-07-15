<?php

namespace Thruway\Common;

use SplQueue;

/**
 * Description of LeakyBucket
 *
 * @author Binaek
 */
class LeakyBucket
{

    protected $maxRate;
    protected $minTime;
    //holds time of last action (past or future!)
    protected $lastSchedAction;

    public function __construct($maxRatePerSecond = -1)
    {
        $this->maxRate = -1;
        $this->setMaxRate($maxRatePerSecond);
        $this->lastSchedAction = time() - $this->minTime;
    }

    public function setMaxRate($maxRatePerSecond)
    {
        if ($maxRatePerSecond > 0.0) {
            $this->maxRate = $maxRatePerSecond;
            //milliseconds between successive calls
            $this->minTime = (int) (1000.0 / $maxRatePerSecond);
        }
    }

    public function canConsume()
    {
        return ($this->getTimeLeft() <= 0);
    }

    public function getTimeLeft()
    {
        $timeLeft = 0;
        if ($this->maxRate > 0) {
            //we are rate limited
            $curTime = time();
            //calculate when can we send back
            $timeLeft = $this->lastSchedAction + $this->minTime - $curTime;
        }
        return $timeLeft;
    }

    public function consume()
    {
        if ($this->canConsume()) {
            $this->lastSchedAction = time();
        }
    }

}
