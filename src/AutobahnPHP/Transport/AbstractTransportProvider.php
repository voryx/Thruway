<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 9:55 PM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;

abstract class AbstractTransportProvider {
    abstract public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop);
} 