<?php


class PublishMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testPublishBlackWhiteListing()
    {
        $publishMessageRawJSON = "[16,12345,{},\"example.topic\"]";
        /** @var \Thruway\Message\PublishMessage $publishMessage */
        $publishMessage = \Thruway\Message\Message::createMessageFromArray(json_decode($publishMessageRawJSON));

        $this->assertInstanceOf('\Thruway\Message\PublishMessage', $publishMessage);
        $this->assertEquals((object)[], $publishMessage->getOptions());
        $this->assertEquals("example.topic", $publishMessage->getTopicName());


    }

}