<?php
namespace ISER\Core\Middleware;

use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Helpers;

class AuthMiddleware
{
    private JWTSession $jwt;

    public function __construct(JWTSession $jwt)
    {
        $this->jwt = $jwt;
    }

    public function handle(): bool
    {
        $token = $this->jwt->getTokenFromRequest();

        if (!$token || !$this->jwt->validate($token)) {
            $currentUrl = Helpers::getCurrentUrl();
            $_SESSION['redirect_after_login'] = $currentUrl;
            Helpers::redirect('/login.php');
            return false;
        }

        return true;
    }

    public function requireAuth(): void
    {
        if (!$this->handle()) {
            exit;
        }
    }
}
