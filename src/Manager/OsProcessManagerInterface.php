<?php

namespace Okvpn\Bundle\MQInsightBundle\Manager;

interface OsProcessManagerInterface
{
    const WINDOWS_OS = 'WINDOWS';
    const LINUX_OS   = 'LINUX';
    const MAC_OS     = 'DARWIN';
    const FREE_BSD   = 'FREEBSD';

    /**
     * @return bool
     */
    public function isApplicableOS(): bool;
}
