<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Manager;

use Symfony\Component\Process\Process;

class UnixProcessManager implements OsProcessManagerInterface
{
    use GetOsTrait;

    /**
     * @param int $pid
     * @return null|string
     */
    public function getPwdx(int $pid)
    {
        $process = new Process(sprintf('pwdx %s', $pid));
        $process->run();
        if ($process->getExitCode() !== 0) {
            return null;
        }

        $output = $process->getOutput();
        return $output;
    }

    /**
     * @param string $command
     * @return string
     */
    protected function run(string $command): string
    {
        $process = new Process($command);
        $process->run();

        return $process->getOutput();
    }

    /**
     * @return bool
     */
    public function isApplicableOS(): bool
    {
        $os = $this->getOs();
        return in_array($os, [self::LINUX_OS, self::MAC_OS]);
    }
}
