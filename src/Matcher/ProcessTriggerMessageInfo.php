<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Matcher;

use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Component\MessageQueue\Transport\MessageInterface;

final class ProcessTriggerMessageInfo implements MessageInfoInterface
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

        if ('oro_workflow.async.execute_process_job' == $marker) {
            $body = json_decode($message->getBody(), true);
            return $body['definition_name'] ?? $marker;
        }

        if (HandleProcessTriggerCommand::NAME == $marker) {
            $body = json_decode($message->getBody(), true);
            return $body['arguments']['--name'] ?? $marker;
        }

        return $marker;
    }
}
