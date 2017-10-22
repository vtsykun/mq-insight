<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Model;

class Counter
{
    protected $counters = [];

    /**
     * @param array $counters
     */
    public function tick(array $counters)
    {
        foreach ($counters as $counter) {
            if (!isset($this->counters[$counter])) {
                $this->counters[$counter] = 0;
            }

            $this->counters[$counter]++;
        }
    }

    public function tickAll()
    {
        foreach ($this->counters as &$counter) {
            $counter++;
        }
    }

    /**
     * @param $counter
     */
    public function add(string $counter)
    {
        $this->counters[$counter] = 0;
    }

    public function reset(string $counter): int
    {
        $count = $this->counters[$counter];
        $this->counters[$counter] = 0;

        return $count;
    }

    public function resetAll()
    {
        foreach ($this->counters as &$counter) {
            $counter = 0;
        }
    }

    public function count(string $counter): int
    {
        if (!isset($this->counters[$counter])) {
            $this->counters[$counter] = 0;
        }

        return $this->counters[$counter];
    }

    public function counters(): array
    {
        return $this->counters;
    }
}
