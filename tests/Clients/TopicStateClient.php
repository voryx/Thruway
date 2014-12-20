<?php

/**
 *
 * For more information go to:
 * http://voryx.net/creating-internal-client-thruway/
 */
class TopicStateClient extends \Thruway\Module\Module
{

    public function onSessionStart($session, $transport)
    {

        $this->getCallee()->register($this->session, 'test.state.topic.handler', [$this, 'giveItState']);

        $registration              = new \stdClass();
        $registration->topic       = "test.state.topic";
        $registration->handler_uri = "test.state.topic.handler";

        $this->getCaller()->call($this->session, 'add_topic_handler', [$registration]);

    }

    public function giveItState($args)
    {
        $topicName = array_shift($args);
        $sessionId = array_shift($args);

        $this->session->publish($topicName, ["testing"], [], ["eligible" => [$sessionId]]);

    }


}