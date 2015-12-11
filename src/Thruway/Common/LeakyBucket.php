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

    /**
     * @var SplQueue The Object Storage
     */
    protected $objectQueue;

    public function __construct($maxRatePerSecond = -1)
    {
        $this->maxRate = -1;
        $this->objectQueue = new SplQueue();
        $this->lastSchedAction = time();
        $this->setMaxRate($maxRatePerSecond);
    }

    public function enqueue($anyObject)
    {
        $this->objectQueue->enqueue($anyObject);
    }

    public function count()
    {
        return $this->objectQueue->count();
    }

    public function setMaxRate($maxRatePerSecond)
    {
        if ($maxRatePerSecond > 0.0) {
            $this->maxRate = $maxRatePerSecond;
            $this->minTime = (int) (1000.0 / $maxRatePerSecond);
        }
    }

    public function consume()
    {
        if ($this->maxRate > 0) {
            //we are rate limited
            $curTime = time();
            //calculate when can we send back
            $timeLeft = $this->lastSchedAction + $this->minTime - $curTime;
            if ($timeLeft > 0) {
                $this->lastSchedAction += $this->minTime;
                //we need to sleep for sometime
                echo "We are sleeping";
                sleep($timeLeft);
            } else {
                $this->lastSchedAction = $this->curTime;
            }
        }
        //lets go back
        return $this->objectQueue->dequeue();
    }

}
