<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Provider;

/**
 * Usually MQ uses FIFO, it means that you will need to go through all the messages to get the desired one.
 * This interface for MQ that allow "random access" for messages
 */
interface RandomAccessQueueInterface
{
    const NOT_SUPPORT = 'not_support';

    /**
     * Delete message by id
     *
     * @param $messageId
     *
     * @return bool|mixed
     */
    public function deleteMessage($messageId);
}
