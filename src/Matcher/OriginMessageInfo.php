<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Matcher;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Transport\MessageInterface;

final class OriginMessageInfo implements MessageInfoInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMarker(MessageInterface $message): string
    {
        return $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
    }
}
