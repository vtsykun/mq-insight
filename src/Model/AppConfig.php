<?php

namespace Okvpn\Bundle\MQInsightBundle\Model;

class AppConfig
{
    protected static $token;

    /**
     * @param string $token
     */
    public static function setToken($token)
    {
        if (self::$token === null) {
            self::$token = $token;
        }
    }

    /**
     * Used to avoid conflicts when using multiple applications on a single server
     *
     * @return int
     */
    public static function getApplicationID()
    {
        return hexdec(substr(sha1(self::$token), 0, 6));
    }
}
