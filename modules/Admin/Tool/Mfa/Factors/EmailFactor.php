<?php
/**
 * ISER MFA System - Email Code Factor
 *
 * @package    ISER\Modules\Admin\Tool\Mfa\Factors
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Admin\Tool\Mfa\Factors;

use ISER\Core\Database\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailFactor implements MfaFactorInterface
{
    private Database $db;
    private int $codeLength;
    private int $codeExpiry;
    private int $maxAttempts;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->codeLength = (int)(getenv('MFA_EMAIL_CODE_LENGTH') ?: 6);
        $this->codeExpiry = (int)(getenv('MFA_EMAIL_CODE_EXPIRY') ?: 600); // 10 minutes
        $this->maxAttempts = (int)(getenv('MFA_EMAIL_MAX_ATTEMPTS') ?: 3);
    }

    public function getName(): string
    {
        return 'email';
    }

    public function getDisplayName(): string
    {
        return 'Código por Email';
    }

    public function getDescription(): string
    {
        return 'Recibe un código de verificación de 6 dígitos en tu email';
    }

    public function isConfigured(int $userId): bool
    {
        // Email factor is always "configured" if user has an email
        $user = $this->db->selectOne('users', ['id' => $userId]);
        return $user && !empty($user['email']);
    }

    public function setup(int $userId, array $data = []): array
    {
        $user = $this->db->selectOne('users', ['id' => $userId]);

        if (!$user || empty($user['email'])) {
            return [
                'success' => false,
                'error' => 'Usuario sin email configurado'
            ];
        }

        // Update user config to enable email factor
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        $userConfig->setUserConfig($userId, $this->getName(), [
            'enabled' => 1,
            'data' => [
                'email' => $user['email']
            ]
        ]);

        return [
            'success' => true,
            'email' => $this->maskEmail($user['email'])
        ];
    }

    public function verify(int $userId, string $code): bool
    {
        // Clean code (remove spaces)
        $code = preg_replace('/\s+/', '', $code);

        if (strlen($code) !== $this->codeLength) {
            return false;
        }

        // Get active code for user
        $sql = "SELECT * FROM {$this->db->table('mfa_email_codes')}
                WHERE userid = :userid
                AND used = 0
                AND expires_at > :now
                AND attempts < :maxattempts
                ORDER BY timecreated DESC
                LIMIT 1";

        $now = time();
        $storedCode = $this->db->getConnection()->fetchOne($sql, [
            ':userid' => $userId,
            ':now' => $now,
            ':maxattempts' => $this->maxAttempts
        ]);

        if (!$storedCode) {
            return false;
        }

        // Increment attempts
        $this->db->update('mfa_email_codes', [
            'attempts' => $storedCode['attempts'] + 1
        ], ['id' => $storedCode['id']]);

        // Verify code
        if ($storedCode['code'] === $code) {
            // Mark as used
            $this->db->update('mfa_email_codes', [
                'used' => 1
            ], ['id' => $storedCode['id']]);

            return true;
        }

        return false;
    }

    public function getSetupTemplate(): string
    {
        return 'factors/email_setup';
    }

    public function getVerifyTemplate(): string
    {
        return 'factors/email_verify';
    }

    public function getSetupData(int $userId): array
    {
        $user = $this->db->selectOne('users', ['id' => $userId]);

        return [
            'factor_name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'email' => $this->maskEmail($user['email'] ?? '')
        ];
    }

    public function getVerifyData(int $userId): array
    {
        $user = $this->db->selectOne('users', ['id' => $userId]);

        return [
            'factor_name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'email' => $this->maskEmail($user['email'] ?? ''),
            'code_length' => $this->codeLength,
            'expiry_minutes' => floor($this->codeExpiry / 60)
        ];
    }

    public function revoke(int $userId): bool
    {
        // Delete all email codes for user
        $this->db->delete('mfa_email_codes', ['userid' => $userId]);

        // Remove user config
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        return $userConfig->removeFactor($userId, $this->getName());
    }

    public function getConfig(int $userId): array|false
    {
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        return $userConfig->getUserConfig($userId, $this->getName());
    }

    public function canBePrimary(): bool
    {
        return true;
    }

    public function getSortOrder(): int
    {
        return 2; // Medium priority
    }

    /**
     * Send verification code by email
     */
    public function sendCode(int $userId): array
    {
        $user = $this->db->selectOne('users', ['id' => $userId]);

        if (!$user || empty($user['email'])) {
            return [
                'success' => false,
                'error' => 'Usuario sin email'
            ];
        }

        // Generate code
        $code = $this->generateCode();

        // Save code to database
        $expiresAt = time() + $this->codeExpiry;
        $this->db->insert('mfa_email_codes', [
            'userid' => $userId,
            'code' => $code,
            'expires_at' => $expiresAt,
            'used' => 0,
            'attempts' => 0,
            'timecreated' => time()
        ]);

        // Send email
        try {
            $this->sendEmail($user['email'], $user['username'], $code);

            return [
                'success' => true,
                'expires_in' => $this->codeExpiry,
                'masked_email' => $this->maskEmail($user['email'])
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al enviar email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate random verification code
     */
    private function generateCode(): string
    {
        $code = '';
        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    /**
     * Send email with verification code
     */
    private function sendEmail(string $to, string $username, string $code): void
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('MAIL_USERNAME');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = (int)getenv('MAIL_PORT');

            // Recipients
            $mail->setFrom(getenv('MAIL_FROM_ADDRESS'), getenv('MAIL_FROM_NAME') ?: 'ISER Auth System');
            $mail->addAddress($to, $username);

            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Código de verificación MFA - ISER';
            $mail->Body = $this->getEmailTemplate($username, $code);
            $mail->AltBody = "Tu código de verificación es: {$code}\n\nEste código expira en " . floor($this->codeExpiry / 60) . " minutos.";

            $mail->send();
        } catch (PHPMailerException $e) {
            throw new \Exception("Error al enviar email: {$mail->ErrorInfo}");
        }
    }

    /**
     * Get HTML email template
     */
    private function getEmailTemplate(string $username, string $code): string
    {
        $expiryMinutes = floor($this->codeExpiry / 60);

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
        .code { background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #667eea; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Código de Verificación MFA</h1>
        </div>
        <div class="content">
            <p>Hola {$username},</p>
            <p>Has solicitado un código de verificación para acceder a tu cuenta en ISER.</p>
            <p>Tu código de verificación es:</p>
            <div class="code">{$code}</div>
            <p><strong>Este código expirará en {$expiryMinutes} minutos.</strong></p>
            <p>Si no solicitaste este código, ignora este mensaje y asegúrate de que tu cuenta esté segura.</p>
        </div>
        <div class="footer">
            <p>ISER Authentication System - Sistema Seguro</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Mask email for display (show first 2 chars and domain)
     */
    private function maskEmail(string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email);
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Clean expired codes
     */
    public function cleanExpiredCodes(): int
    {
        $sql = "DELETE FROM {$this->db->table('mfa_email_codes')}
                WHERE expires_at < :now OR (used = 1 AND timecreated < :old)";

        return $this->db->getConnection()->execute($sql, [
            ':now' => time(),
            ':old' => time() - 86400 // Delete used codes older than 1 day
        ]);
    }
}
