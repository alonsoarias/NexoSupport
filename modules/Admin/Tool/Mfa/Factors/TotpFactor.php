<?php
/**
 * ISER MFA System - TOTP Factor (Google Authenticator)
 *
 * @package    ISER\Modules\Admin\Tool\Mfa\Factors
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Modules\Admin\Tool\Mfa\Factors;

use ISER\Core\Database\Database;

class TotpFactor implements MfaFactorInterface
{
    private Database $db;
    private string $issuer;
    private int $digits;
    private int $period;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->issuer = getenv('MFA_TOTP_ISSUER') ?: 'ISER Auth System';
        $this->digits = (int)(getenv('MFA_TOTP_DIGITS') ?: 6);
        $this->period = (int)(getenv('MFA_TOTP_PERIOD') ?: 30);
    }

    public function getName(): string
    {
        return 'totp';
    }

    public function getDisplayName(): string
    {
        return 'Aplicación Autenticadora (TOTP)';
    }

    public function getDescription(): string
    {
        return 'Usa una aplicación como Google Authenticator o Authy para generar códigos de verificación';
    }

    public function isConfigured(int $userId): bool
    {
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        $config = $userConfig->getUserConfig($userId, $this->getName());
        return $config && !empty($config['secret']) && $config['enabled'] == 1;
    }

    public function setup(int $userId, array $data = []): array
    {
        // Generate secret
        $secret = $this->generateSecret();

        // Get user email for QR code
        $user = $this->db->selectOne('users', ['id' => $userId]);
        $accountName = $user['email'] ?? $user['username'];

        // Save configuration
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        $userConfig->setUserConfig($userId, $this->getName(), [
            'secret' => $secret,
            'enabled' => 1,
            'data' => [
                'digits' => $this->digits,
                'period' => $this->period,
                'algorithm' => 'SHA1'
            ]
        ]);

        // Generate QR code data
        $otpauthUrl = $this->getOtpauthUrl($accountName, $secret);

        return [
            'success' => true,
            'secret' => $secret,
            'formatted_secret' => $this->formatSecret($secret),
            'qr_code_url' => $this->getQrCodeUrl($otpauthUrl),
            'otpauth_url' => $otpauthUrl
        ];
    }

    public function verify(int $userId, string $code): bool
    {
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        $config = $userConfig->getUserConfig($userId, $this->getName());

        if (!$config || empty($config['secret'])) {
            return false;
        }

        $secret = $config['secret'];

        // Verify current time window and 1 window before/after for clock drift
        $timeStep = floor(time() / $this->period);

        for ($i = -1; $i <= 1; $i++) {
            if ($this->generateOTP($secret, $timeStep + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    public function getSetupTemplate(): string
    {
        return 'factors/totp_setup';
    }

    public function getVerifyTemplate(): string
    {
        return 'factors/totp_verify';
    }

    public function getSetupData(int $userId): array
    {
        return [
            'factor_name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'issuer' => $this->issuer,
            'digits' => $this->digits
        ];
    }

    public function getVerifyData(int $userId): array
    {
        return [
            'factor_name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'digits' => $this->digits
        ];
    }

    public function revoke(int $userId): bool
    {
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
        return 1; // Highest priority
    }

    /**
     * Generate random secret (base32 encoded)
     */
    private function generateSecret(int $length = 32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    /**
     * Format secret for display (groups of 4)
     */
    private function formatSecret(string $secret): string
    {
        return implode(' ', str_split($secret, 4));
    }

    /**
     * Generate OTP for given time step
     */
    private function generateOTP(string $secret, int $timeStep): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeStep);

        $hash = hash_hmac('sha1', $time, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, $this->digits);

        return str_pad((string)$code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string
     */
    private function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];

        if (!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }

        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount === $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) !== str_repeat('=', $allowedValues[$i])) {
                return '';
            }
        }

        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';

        foreach ($secret as $char) {
            if (!isset($base32charsFlipped[$char])) {
                return '';
            }
            $binaryString .= str_pad(decbin($base32charsFlipped[$char]), 5, '0', STR_PAD_LEFT);
        }

        $eightBits = str_split($binaryString, 8);
        $decoded = '';

        foreach ($eightBits as $bin) {
            if (strlen($bin) === 8) {
                $decoded .= chr(bindec($bin));
            }
        }

        return $decoded;
    }

    /**
     * Get otpauth:// URL for QR code
     */
    private function getOtpauthUrl(string $accountName, string $secret): string
    {
        $params = [
            'secret' => $secret,
            'issuer' => $this->issuer,
            'digits' => $this->digits,
            'period' => $this->period
        ];

        $queryString = http_build_query($params);
        $encodedIssuer = rawurlencode($this->issuer);
        $encodedAccount = rawurlencode($accountName);

        return "otpauth://totp/{$encodedIssuer}:{$encodedAccount}?{$queryString}";
    }

    /**
     * Get QR code image URL (using Google Charts API or similar)
     */
    private function getQrCodeUrl(string $data): string
    {
        // Using Google Charts API for QR code generation
        // Alternative: use a local QR code library
        $encodedData = urlencode($data);
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={$encodedData}";
    }

    /**
     * Get current OTP (for testing/display purposes)
     */
    public function getCurrentOTP(string $secret): string
    {
        $timeStep = floor(time() / $this->period);
        return $this->generateOTP($secret, $timeStep);
    }
}
