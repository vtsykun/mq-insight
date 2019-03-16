<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Matcher;

use Oro\Component\MessageQueue\Transport\MessageInterface;

final class CronMessageInfo implements MessageInfoInterface
{
    protected $wrapped;

    public function __construct(MessageInfoInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function getMarker(MessageInterface $message): string
    {
        $marker = $this->wrapped->getMarker($message);

        if ('oro_cron.async.command_runner_processor' === $marker || 'oro_cron.async.command_runner_message_processor' === $marker) {
            $body = $message->getBody();
            $body = json_decode($body, true);

            return $body['command'] ?? $marker;
        }

        return $marker;
    }
}
