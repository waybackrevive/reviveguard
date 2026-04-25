<?php
defined('ABSPATH') || exit;

/**
 * Internal debug logger — writes to a rotating log in wp-content/uploads/reviveguard/.
 * Never uses error_log() or any WP error reporting that could pollute client logs.
 */
final class ReviveGuard_DebugLogger
{
    private const MAX_FILE_SIZE_BYTES = 524288; // 512 KB

    private static function logDir(): string
    {
        return WP_CONTENT_DIR . '/uploads/reviveguard/';
    }

    private static function logFile(): string
    {
        return self::logDir() . 'debug.log';
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    public static function warning(string $message): void
    {
        self::write('WARNING', $message);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    private static function write(string $level, string $message): void
    {
        $dir = self::logDir();

        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
            // Prevent direct URL access to the log
            file_put_contents($dir . '.htaccess', 'Deny from all');
        }

        $logFile = self::logFile();

        // Rotate if over max size
        if (file_exists($logFile) && filesize($logFile) > self::MAX_FILE_SIZE_BYTES) {
            rename($logFile, $logFile . '.1');
        }

        $line = sprintf(
            "[%s] [%s] %s\n",
            gmdate('Y-m-d H:i:s'),
            $level,
            $message
        );

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
