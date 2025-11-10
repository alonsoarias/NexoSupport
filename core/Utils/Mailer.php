<?php
/**
 * ISER - Mailer Utility
 *
 * Handles email sending with PHPMailer using dynamic configuration from SettingsManager.
 * Supports SMTP and provides template-based email generation.
 *
 * @package    ISER\Core\Utils
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Core\Utils;

use ISER\Core\Config\SettingsManager;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private SettingsManager $settings;
    private PHPMailer $mailer;

    public function __construct(SettingsManager $settings)
    {
        $this->settings = $settings;
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with dynamic settings
     *
     * @return void
     */
    private function configure(): void
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->settings->getString('smtphosts', 'core');
            $this->mailer->Port = $this->settings->getInt('smtpport', 'core', 587);

            // Authentication
            $smtpUser = $this->settings->getString('smtpuser', 'core');
            if (!empty($smtpUser)) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $smtpUser;
                $this->mailer->Password = $this->settings->getString('smtppass', 'core');
            }

            // Encryption
            $smtpSecure = $this->settings->getString('smtpsecure', 'core', 'tls');
            if ($smtpSecure === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtpSecure === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            // Sender
            $fromAddress = $this->settings->getString('noreplyaddress', 'core', 'noreply@localhost');
            $fromName = $this->settings->getString('emailfromname', 'core', 'ISER System');
            $this->mailer->setFrom($fromAddress, $fromName);

            // Encoding
            $this->mailer->CharSet = 'UTF-8';

        } catch (Exception $e) {
            Logger::error('Mailer configuration failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send email
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string|null $altBody Plain text alternative body
     * @return bool True on success
     */
    public function send(string|array $to, string $subject, string $body, ?string $altBody = null): bool
    {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Add recipient(s)
            if (is_array($to)) {
                foreach ($to as $email) {
                    $this->mailer->addAddress($email);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?? strip_tags($body);

            // Send
            $result = $this->mailer->send();

            if ($result) {
                Logger::auth('Email sent successfully', [
                    'to' => is_array($to) ? implode(', ', $to) : $to,
                    'subject' => $subject
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Logger::error('Email sending failed', [
                'error' => $e->getMessage(),
                'to' => is_array($to) ? implode(', ', $to) : $to
            ]);
            return false;
        }
    }

    /**
     * Send test email
     *
     * @param string $to Recipient email
     * @return array Result with success boolean and message
     */
    public function sendTestEmail(string $to): array
    {
        $subject = 'Email de Prueba - ISER System';
        $body = $this->getTestEmailTemplate();

        if ($this->send($to, $subject, $body)) {
            return [
                'success' => true,
                'message' => "Email de prueba enviado exitosamente a {$to}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al enviar el email de prueba. Verifica la configuración SMTP.'
            ];
        }
    }

    /**
     * Send welcome email to new user
     *
     * @param string $to Recipient email
     * @param string $username Username
     * @param string $password Temporary password
     * @return bool True on success
     */
    public function sendWelcomeEmail(string $to, string $username, string $password): bool
    {
        $siteName = $this->settings->getString('sitename', 'core', 'ISER');
        $subject = "Bienvenido a {$siteName}";
        $body = $this->getWelcomeEmailTemplate($username, $password);

        return $this->send($to, $subject, $body);
    }

    /**
     * Send password reset email
     *
     * @param string $to Recipient email
     * @param string $username Username
     * @param string $resetLink Reset link
     * @return bool True on success
     */
    public function sendPasswordResetEmail(string $to, string $username, string $resetLink): bool
    {
        $siteName = $this->settings->getString('sitename', 'core', 'ISER');
        $subject = "Recuperación de Contraseña - {$siteName}";
        $body = $this->getPasswordResetTemplate($username, $resetLink);

        return $this->send($to, $subject, $body);
    }

    /**
     * Get test email template
     *
     * @return string HTML template
     */
    private function getTestEmailTemplate(): string
    {
        $siteName = $this->settings->getString('sitename', 'core', 'ISER');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Email de Prueba</h1>
        </div>
        <div class="content">
            <p>Este es un email de prueba del sistema {$siteName}.</p>
            <p>Si recibiste este mensaje, significa que la configuración de email está funcionando correctamente.</p>
            <p><strong>Configuración actual:</strong></p>
            <ul>
                <li>Servidor SMTP: {$this->settings->getString('smtphosts', 'core')}</li>
                <li>Puerto: {$this->settings->getInt('smtpport', 'core')}</li>
                <li>Encriptación: {$this->settings->getString('smtpsecure', 'core')}</li>
            </ul>
        </div>
        <div class="footer">
            <p>{$siteName} - Sistema de Autenticación ISER</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get welcome email template
     *
     * @param string $username Username
     * @param string $password Password
     * @return string HTML template
     */
    private function getWelcomeEmailTemplate(string $username, string $password): string
    {
        $siteName = $this->settings->getString('sitename', 'core', 'ISER');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .credentials { background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a {$siteName}!</h1>
        </div>
        <div class="content">
            <p>Hola {$username},</p>
            <p>Tu cuenta ha sido creada exitosamente. A continuación encontrarás tus credenciales de acceso:</p>

            <div class="credentials">
                <p><strong>Usuario:</strong> {$username}</p>
                <p><strong>Contraseña temporal:</strong> {$password}</p>
            </div>

            <p><strong>Importante:</strong> Por seguridad, te recomendamos cambiar tu contraseña después de tu primer inicio de sesión.</p>

            <a href="http://localhost/login.php" class="button">Iniciar Sesión</a>
        </div>
        <div class="footer">
            <p>{$siteName} - Sistema de Autenticación ISER</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get password reset template
     *
     * @param string $username Username
     * @param string $resetLink Reset link
     * @return string HTML template
     */
    private function getPasswordResetTemplate(string $username, string $resetLink): string
    {
        $siteName = $this->settings->getString('sitename', 'core', 'ISER');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recuperación de Contraseña</h1>
        </div>
        <div class="content">
            <p>Hola {$username},</p>
            <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente botón para crear una nueva contraseña:</p>

            <a href="{$resetLink}" class="button">Restablecer Contraseña</a>

            <div class="warning">
                <strong>Nota:</strong> Este enlace expirará en 24 horas. Si no solicitaste este cambio, ignora este mensaje y tu contraseña permanecerá sin cambios.
            </div>
        </div>
        <div class="footer">
            <p>{$siteName} - Sistema de Autenticación ISER</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get PHPMailer instance
     *
     * @return PHPMailer
     */
    public function getMailer(): PHPMailer
    {
        return $this->mailer;
    }
}
