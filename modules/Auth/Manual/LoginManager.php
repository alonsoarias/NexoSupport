<?php
namespace ISER\Modules\Auth\Manual;

use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Recaptcha;
use ISER\Core\Utils\Logger;

class LoginManager
{
    private AuthManual $auth;
    private JWTSession $jwt;
    private Recaptcha $recaptcha;

    public function __construct(AuthManual $auth, JWTSession $jwt, Recaptcha $recaptcha)
    {
        $this->auth = $auth;
        $this->jwt = $jwt;
        $this->recaptcha = $recaptcha;
    }

    public function processLogin(array $data): array
    {
        // Validate reCAPTCHA
        if ($this->recaptcha->isEnabled()) {
            if (empty($data['g-recaptcha-response']) || !$this->recaptcha->verify($data['g-recaptcha-response'])) {
                Logger::security('reCAPTCHA validation failed');
                return ['success' => false, 'error' => 'reCAPTCHA validation failed'];
            }
        }

        // Authenticate
        $user = $this->auth->authenticate([
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? ''
        ]);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Generate JWT tokens
        $tokens = $this->jwt->generateTokenPair($user);

        return [
            'success' => true,
            'user' => $user,
            'tokens' => $tokens
        ];
    }

    public function logout(string $token): bool
    {
        return $this->auth->logout($token);
    }
}
