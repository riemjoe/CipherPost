<?php

namespace Postcardarchive\Utils;
class UtilsLogging
{
    /**
     * Returns the string representation of a log level.
     * @param mixed $level
     * @return string
     */
    private static function logLevelToString($level): string
    {
        switch ($level)
        {
            case 0: return 'DEBUG';
            case 1: return 'INFO';
            case 2: return 'WARNING';
            case 3: return 'ERROR';
            default: return 'UNKNOWN';
        }
    }

    /**
     * Returns the configured log level.
     * @return int
     */
    private static function getLogLevel(): int
    {
        $config = new UtilsConfiguration("app");
        return $config->get('log-level', 1);
    }

    /**
     * Returns the log file path.
     * @return string
     */
    private static function getLogPath()
    {
        $config = new UtilsConfiguration("app");
        $path = $config->get('log-path', 'data/logs/app.log');
        return dirname(__DIR__, 2) . '/' . $path;
    }

    /**
     * Logs a message to the log file if the level is sufficient.
     * @param mixed $level
     * @param mixed $message
     * @return void
     */
    private static function log($level, $message)
    {
        if($level < self::getLogLevel()) return;
        if(!is_dir(dirname(self::getLogPath())))
        {
            mkdir(dirname(self::getLogPath()), 0777, true);
        }
        $date = date('Y-m-d H:i:s');
        $levelStr = self::logLevelToString($level);
        file_put_contents(self::getLogPath(), "[$date] [$levelStr] $message" . PHP_EOL, FILE_APPEND);
    }


    public static function debug($message)
    {
        self::log(0, $message);
    }

    public static function info($message)
    {
        self::log(1, $message);
    }

    public static function warning($message)
    {
        self::log(2, $message);
    }

    public static function error($message)
    {
        self::log(3, $message);
    }
}


?>