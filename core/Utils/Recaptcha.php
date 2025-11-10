<?php
/**
 * ISER - reCAPTCHA v2 Integration
 * @package ISER\Core\Utils
 */

namespace ISER\Core\Utils;

class Recaptcha
{
    private string $siteKey;
    private string $secretKey;
    private bool $enabled;

    public function __construct(string $siteKey, string $secretKey, bool $enabled = true)
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->enabled = $enabled;
    }

    public function renderWidget(): string
    {
        if (!$this->enabled) return '';
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($this->siteKey) . '"></div>';
    }

    public function getScriptTag(): string
    {
        if (!$this->enabled) return '';
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    public function verify(string $response, ?string $remoteIp = null): bool
    {
        if (!$this->enabled) return true;
        if (empty($response)) return false;

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->secretKey,
            'response' => $response,
            'remoteip' => $remoteIp ?? Helpers::getClientIp()
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) return false;

        $json = json_decode($result, true);
        return isset($json['success']) && $json['success'] === true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
