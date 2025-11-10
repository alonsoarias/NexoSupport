<?php

declare(strict_types=1);

namespace ISER\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Logger - Implementación PSR-3
 *
 * Wrapper para Monolog que implementa PSR-3 Logger Interface
 *
 * @package ISER\Core\Log
 */
class Logger implements LoggerInterface
{
    private static ?Logger $instance = null;
    private MonologLogger $logger;
    private string $logsPath;
    private string $channel = 'iser';

    /**
     * Constructor privado (Singleton)
     *
     * @param string $logsPath Ruta al directorio de logs
     * @param string $channel Nombre del canal
     */
    private function __construct(string $logsPath, string $channel = 'iser')
    {
        $this->logsPath = $logsPath;
        $this->channel = $channel;
        $this->logger = new MonologLogger($channel);
        $this->configureHandlers();
    }

    /**
     * Obtener instancia única
     *
     * @param string $logsPath Ruta al directorio de logs
     * @param string $channel Nombre del canal
     * @return self
     */
    public static function getInstance(string $logsPath = '', string $channel = 'iser'): self
    {
        if (self::$instance === null) {
            if (empty($logsPath)) {
                $logsPath = dirname(__DIR__, 3) . '/var/logs';
            }

            if (!is_dir($logsPath)) {
                mkdir($logsPath, 0755, true);
            }

            self::$instance = new self($logsPath, $channel);
        }

        return self::$instance;
    }

    /**
     * Configurar handlers de Monolog
     */
    private function configureHandlers(): void
    {
        // Formato de línea
        $dateFormat = 'Y-m-d H:i:s';
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);

        // Handler para logs diarios (rotación automática)
        $rotatingHandler = new RotatingFileHandler(
            $this->logsPath . '/iser.log',
            30, // 30 días de retención
            MonologLogger::DEBUG
        );
        $rotatingHandler->setFormatter($formatter);
        $this->logger->pushHandler($rotatingHandler);

        // Handler para errores críticos en archivo separado
        $errorHandler = new StreamHandler(
            $this->logsPath . '/error.log',
            MonologLogger::ERROR
        );
        $errorHandler->setFormatter($formatter);
        $this->logger->pushHandler($errorHandler);
    }

    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, (string)$message, $context);
    }

    /**
     * Crear un nuevo canal de logging
     *
     * @param string $channel Nombre del canal
     * @return self Nueva instancia de Logger
     */
    public function channel(string $channel): self
    {
        return new self($this->logsPath, $channel);
    }

    /**
     * Obtener el logger Monolog subyacente
     *
     * @return MonologLogger
     */
    public function getMonolog(): MonologLogger
    {
        return $this->logger;
    }
}
