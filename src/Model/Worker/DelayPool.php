<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Model\Worker;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class DelayPool implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    protected $tasks = [];

    /** @var array */
    protected $lastSync = [];

    /**
     * DelayPool constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }


    /**
     * @param TaskInterface $task
     * @param int $delay. In second
     * @param string $taskname
     */
    public function submit(TaskInterface $task, int $delay = 0, string $taskname = '')
    {
        $this->tasks[] = [
            0 => $delay,
            1 => $task,
            2 => $taskname
        ];

        $this->lastSync[] = 0;
    }

    public function sync()
    {
        $currentTime = time();
        /** @var TaskInterface $task */
        foreach ($this->tasks as $taskId => list($delay, $task, $taskname)) {
            if ($currentTime - $this->lastSync[$taskId] >= $delay) {
                $this->logger->debug("Interval:$delay sec. Run sync for \"$taskname\"");
                $task->run(...func_get_args());
                $this->lastSync[$taskId] = $currentTime;
            }
        }
    }
}
