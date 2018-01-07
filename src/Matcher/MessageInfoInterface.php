<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Matcher;

use Oro\Component\MessageQueue\Transport\MessageInterface;

interface MessageInfoInterface
{

    /**
     * @param MessageInterface $message
     * @return string
     */
    public function getMarker(MessageInterface $message): string;
}
