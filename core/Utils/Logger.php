<?php

/**
 * ISER Authentication System - Logging Manager
 *
 * Manages system logging using Monolog.
 *
 * @package    ISER\Core\Utils
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

/**
 * Logger Class
 *
 * Provides centralized logging functionality with multiple channels.
 */
class Logger
{
    /**
     * Logger instances by channel
     */
    private static array $loggers = [];

    /**
     * Log directory path
     */
    private static string $logPath;

    /**
     * Log level
     */
    private static Level $logLevel;

    /**
     * Maximum log files for rotation
     */
    private static int $maxFiles = 14;

    /**
     * Available log channels
     */
    private const CHANNELS = [
        'system',
        'auth',
        'database',
        'security',
        'api',
        'error',
    ];

    /**
     * Initialize logger configuration
     *
     * @param string $logPath Log directory path
     * @param string $logLevel Log level (debug, info, warning, error)
     * @param int $maxFiles Maximum files for rotation
     * @return void
     */
    public static function init(string $logPath, string $logLevel = 'debug', int $maxFiles = 14): void
    {
        self::$logPath = rtrim($logPath, '/');
        self::$logLevel = self::parseLevel($logLevel);
        self::$maxFiles = $maxFiles;

        // Ensure log directory exists
        if (!is_dir(dirname(self::$logPath))) {
            mkdir(dirname(self::$logPath), 0755, true);
        }
    }

    /**
     * Parse log level string to Monolog Level
     *
     * @param string $level Level string
     * @return Level Monolog Level
     */
    private static function parseLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Debug,
        };
    }

    /**
     * Get logger for specific channel
     *
     * @param string $channel Channel name
     * @return MonologLogger Logger instance
     */
    public static function channel(string $channel = 'system'): MonologLogger
    {
        if (!isset(self::$loggers[$channel])) {
            self::$loggers[$channel] = self::createLogger($channel);
        }

        return self::$loggers[$channel];
    }

    /**
     * Create a new logger instance
     *
     * @param string $channel Channel name
     * @return MonologLogger Logger instance
     */
    private static function createLogger(string $channel): MonologLogger
    {
        $logger = new MonologLogger($channel);

        // Create rotating file handler
        $logFile = self::getLogFilePath($channel);
        $handler = new RotatingFileHandler(
            $logFile,
            self::$maxFiles,
            self::$logLevel
        );

        // Set custom formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * Get log file path for channel
     *
     * @param string $channel Channel name
     * @return string Log file path
     */
    private static function getLogFilePath(string $channel): string
    {
        $logDir = dirname(self::$logPath);
        return $logDir . '/' . $channel . '.log';
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function debug(string $message, array $context = [], string $channel = 'system'): void
    {
        self::channel($channel)->debug($message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function info(string $message, array $context = [], string $channel = 'system'): void
    {
        self::channel($channel)->info($message, $context);
    }

    /**
     * Log notice message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function notice(string $message, array $context = [], string $channel = 'system'): void
    {
        self::channel($channel)->notice($message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function warning(string $message, array $context = [], string $channel = 'system'): void
    {
        self::channel($channel)->warning($message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function error(string $message, array $context = [], string $channel = 'error'): void
    {
        self::channel($channel)->error($message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function critical(string $message, array $context = [], string $channel = 'error'): void
    {
        self::channel($channel)->critical($message, $context);
    }

    /**
     * Log alert message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function alert(string $message, array $context = [], string $channel = 'error'): void
    {
        self::channel($channel)->alert($message, $context);
    }

    /**
     * Log emergency message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @param string $channel Channel name
     * @return void
     */
    public static function emergency(string $message, array $context = [], string $channel = 'error'): void
    {
        self::channel($channel)->emergency($message, $context);
    }

    /**
     * Log authentication event
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public static function auth(string $message, array $context = []): void
    {
        self::channel('auth')->info($message, $context);
    }

    /**
     * Log database event
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public static function database(string $message, array $context = []): void
    {
        self::channel('database')->info($message, $context);
    }

    /**
     * Log security event
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public static function security(string $message, array $context = []): void
    {
        self::channel('security')->warning($message, $context);
    }

    /**
     * Log API event
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public static function api(string $message, array $context = []): void
    {
        self::channel('api')->info($message, $context);
    }

    /**
     * Log exception
     *
     * @param \Throwable $exception Exception to log
     * @param string $channel Channel name
     * @return void
     */
    public static function exception(\Throwable $exception, string $channel = 'error'): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        self::error('Exception caught: ' . $exception->getMessage(), $context, $channel);
    }

    /**
     * Get available channels
     *
     * @return array List of channels
     */
    public static function getChannels(): array
    {
        return self::CHANNELS;
    }

    /**
     * Check if logger is initialized
     *
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return isset(self::$logPath);
    }

    /**
     * Clear all log files
     *
     * @return bool True on success
     */
    public static function clearLogs(): bool
    {
        if (!self::isInitialized()) {
            return false;
        }

        $logDir = dirname(self::$logPath);
        $files = glob($logDir . '/*.log*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Get log file size
     *
     * @param string $channel Channel name
     * @return int File size in bytes
     */
    public static function getLogSize(string $channel = 'system'): int
    {
        $logFile = self::getLogFilePath($channel);

        if (file_exists($logFile)) {
            return filesize($logFile);
        }

        return 0;
    }

    /**
     * Read recent log entries
     *
     * @param string $channel Channel name
     * @param int $lines Number of lines to read
     * @return array Log entries
     */
    public static function tail(string $channel = 'system', int $lines = 50): array
    {
        $logFile = self::getLogFilePath($channel);

        if (!file_exists($logFile)) {
            return [];
        }

        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);

        $entries = [];
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $entries[] = $line;
            }
            $file->next();
        }

        return $entries;
    }
}
