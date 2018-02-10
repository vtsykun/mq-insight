<?php

namespace Okvpn\Bundle\MQInsightBundle\Manager;

use Symfony\Component\Process\Process;

/**
 * @internal
 */
class ProcessManager
{
    /**
     * @param string $command  The command name or prefix
     * @return bool
     */
    public static function isProcessRunning($command)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd = 'WMIC path win32_process get Processid,Commandline | findstr %s | findstr /V findstr';
        } else {
            $cmd = 'ps ax | grep %s | grep -v grep';
        }

        $cmd = sprintf($cmd, $command);

        $process = new Process($cmd);
        $process->run();

        return !empty($process->getOutput());
    }

    /**
     * @param string $command
     * @param bool $excludeParent
     *
     * @return array
     */
    public static function getPidsOfRunningProcess($command, $excludeParent = true)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd = 'WMIC path win32_process get Processid,Commandline | findstr %s | findstr /V findstr';
            $searchRegExp = '/\s+(\d+)\s*$/Usm';
        } else {
            $cmd = 'ps ax | grep %s | grep -v grep';
            $searchRegExp = '/^\s*(\d+)\s+/Usm';
        }

        $cmd = sprintf($cmd, $command);

        $process = new Process($cmd);
        $process->run();
        $output = $process->getOutput();

        $pids = [];
        $lines = preg_split('/$\R?^/m', $output);
        foreach ($lines as $line) {
            preg_match($searchRegExp, $line, $matches);
            if (count($matches) > 1 && !empty($matches[1])) {
                $pids[] = (int) $matches[1];
            }
        }

        if (true === $excludeParent) {
            $parents = [];
            foreach ($pids as $pid) {
                $parents[] = self::getParentPid($pid);
            }

            $pids = array_diff($pids, $parents);
        }
        return $pids;
    }

    /**
     * @param string $command The command name or prefix
     * @param $excludeParent
     *
     * @return int
     */
    public static function getNumberOfRunningProcess($command, $excludeParent = true)
    {
        $pids = self::getPidsOfRunningProcess($command, $excludeParent);

        return count($pids);
    }

    /**
     * @param int $childPid
     * @return int|false|null
     */
    public static function getParentPid($childPid)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return false; // not implements
        }

        $process = new Process("ps -o ppid= -p $childPid");
        $process->run();
        $output = $process->getOutput();

        return $output ? (int) $output : null;
    }

    public static function getProcessNameByPid($pid)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return false; // not implements
        }

        $process = new Process("ps -p $pid -o comm=");
        $process->run();
        $output = $process->getOutput();

        return (string) $output;
    }
}
