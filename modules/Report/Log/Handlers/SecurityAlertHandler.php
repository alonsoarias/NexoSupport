<?php
/**
 * Handler de Monolog para detectar y crear alertas de seguridad
 * @package Report\Log\Handlers
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Report\Log\Handlers;

use ISER\Report\Log\LogManager;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class SecurityAlertHandler extends AbstractProcessingHandler
{
    private LogManager $logManager;
    private array $alertPatterns = [];

    public function __construct(LogManager $logManager, Level $level = Level::Warning)
    {
        parent::__construct($level);
        $this->logManager = $logManager;

        // Definir patrones que generan alertas automáticas
        $this->alertPatterns = [
            'authentication_failure' => [
                'pattern' => '/failed.*login|authentication.*failed|invalid.*credentials/i',
                'type' => 'authentication_failure',
                'severity' => 1
            ],
            'authorization_failure' => [
                'pattern' => '/access.*denied|permission.*denied|unauthorized/i',
                'type' => 'authorization_failure',
                'severity' => 2
            ],
            'sql_injection_attempt' => [
                'pattern' => '/select.*from|union.*select|drop.*table|exec\(|eval\(/i',
                'type' => 'sql_injection_attempt',
                'severity' => 4
            ],
            'xss_attempt' => [
                'pattern' => '/<script|javascript:|onerror=|onload=/i',
                'type' => 'xss_attempt',
                'severity' => 3
            ],
            'suspicious_file_access' => [
                'pattern' => '/\.\.|\/etc\/|\/var\/|config\.php|\.env/i',
                'type' => 'suspicious_file_access',
                'severity' => 3
            ]
        ];
    }

    /**
     * Procesar log y detectar patrones de alerta
     */
    protected function write(LogRecord $record): void
    {
        $message = $record->message;
        $context = $record->context;

        // Verificar patrones de alerta
        foreach ($this->alertPatterns as $name => $config) {
            if (preg_match($config['pattern'], $message)) {
                $this->createAlert($name, $config, $record);
                break;
            }
        }

        // Alertas basadas en contexto
        if (isset($context['security_event'])) {
            $this->createContextualAlert($context, $record);
        }
    }

    /**
     * Crear alerta desde patrón
     */
    private function createAlert(string $name, array $config, LogRecord $record): void
    {
        $this->logManager->createSecurityAlert([
            'type' => $config['type'],
            'severity' => $config['severity'],
            'title' => $this->generateTitle($name),
            'description' => $record->message,
            'details' => [
                'channel' => $record->channel,
                'level' => $record->level->name,
                'context' => $record->context,
                'extra' => $record->extra
            ],
            'userid' => $record->context['userid'] ?? ($_SESSION['user_id'] ?? null),
            'ip_address' => $record->context['ip_address'] ?? $this->getClientIp(),
            'source_component' => $record->channel
        ]);
    }

    /**
     * Crear alerta desde contexto
     */
    private function createContextualAlert(array $context, LogRecord $record): void
    {
        $event = $context['security_event'];

        $this->logManager->createSecurityAlert([
            'type' => $event['type'] ?? 'security_event',
            'severity' => $event['severity'] ?? 2,
            'title' => $event['title'] ?? 'Evento de Seguridad',
            'description' => $record->message,
            'details' => $context,
            'userid' => $context['userid'] ?? ($_SESSION['user_id'] ?? null),
            'ip_address' => $context['ip_address'] ?? $this->getClientIp(),
            'source_component' => $record->channel
        ]);
    }

    /**
     * Generar título legible para alerta
     */
    private function generateTitle(string $name): string
    {
        $titles = [
            'authentication_failure' => 'Fallo de Autenticación',
            'authorization_failure' => 'Acceso No Autorizado',
            'sql_injection_attempt' => 'Intento de Inyección SQL',
            'xss_attempt' => 'Intento de XSS',
            'suspicious_file_access' => 'Acceso Sospechoso a Archivos'
        ];

        return $titles[$name] ?? ucwords(str_replace('_', ' ', $name));
    }

    /**
     * Obtener IP del cliente
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
