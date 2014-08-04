<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 9:55 PM
 */

namespace Thruway\Transport;


use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use React\EventLoop\LoopInterface;

abstract class AbstractTransportProvider {
    /**
     * @param AbstractPeer $peer
     * @param LoopInterface $loop
     */
    abstract public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop);

    /**
     * @return ManagerInterface
     */
    abstract public function getManager();

    /**
     * @param ManagerInterface $managerInterface
     */
    abstract public function setManager(ManagerInterface $managerInterface);
} 