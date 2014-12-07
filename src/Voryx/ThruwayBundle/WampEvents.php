<?php


namespace Voryx\ThruwayBundle;

/**
 * Contains all events thrown in the WampKernel component
 *
 */
final class WampEvents
{
    /**
     * The OPEN event occurs when the WAMP connection is opened
     *
     * @Event
     *
     * @var string
     *
     */
    const OPEN = 'wamp.open';

}
