<?php

/**
 * ISER Authentication System - JWT Session Manager
 *
 * Manages user sessions using JWT tokens.
 *
 * @package    ISER\Core\Session
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Session;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use ISER\Core\Utils\Logger;
use RuntimeException;

/**
 * JWTSession Class
 *
 * Handles JWT token generation, validation, and session management.
 */
class JWTSession
{
    /**
     * JWT secret key
     */
    private string $secret;

    /**
     * JWT algorithm
     */
    private string $algorithm;

    /**
     * Token expiration time (seconds)
     */
    private int $expiration;

    /**
     * Refresh token expiration time (seconds)
     */
    private int $refreshExpiration;

    /**
     * Current decoded token
     */
    private ?object $currentToken = null;

    /**
     * Constructor
     *
     * @param array $config JWT configuration
     */
    public function __construct(array $config)
    {
        $this->secret = $config['secret'] ?? throw new RuntimeException('JWT secret is required');
        $this->algorithm = $config['algorithm'] ?? 'HS256';
        $this->expiration = $config['expiration'] ?? 3600;
        $this->refreshExpiration = $config['refresh_expiration'] ?? 604800;
    }

    /**
     * Generate JWT token
     *
     * @param array $payload Token payload
     * @param bool $isRefreshToken Whether this is a refresh token
     * @return string JWT token
     */
    public function generate(array $payload, bool $isRefreshToken = false): string
    {
        $now = time();
        $exp = $isRefreshToken ? $this->refreshExpiration : $this->expiration;

        $tokenPayload = array_merge([
            'iat' => $now,
            'exp' => $now + $exp,
            'type' => $isRefreshToken ? 'refresh' : 'access',
        ], $payload);

        Logger::auth('JWT token generated', [
            'user_id' => $payload['user_id'] ?? null,
            'type' => $tokenPayload['type'],
            'exp' => $tokenPayload['exp'],
        ]);

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    /**
     * Validate and decode JWT token
     *
     * @param string $token JWT token
     * @return object|false Decoded token or false on failure
     */
    public function validate(string $token): object|false
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            $this->currentToken = $decoded;

            Logger::auth('JWT token validated', [
                'user_id' => $decoded->user_id ?? null,
                'type' => $decoded->type ?? 'unknown',
            ]);

            return $decoded;

        } catch (ExpiredException $e) {
            Logger::auth('JWT token expired', ['token' => substr($token, 0, 20) . '...']);
            return false;

        } catch (\Exception $e) {
            Logger::error('JWT validation failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...',
            ]);
            return false;
        }
    }

    /**
     * Generate access and refresh tokens
     *
     * @param array $userData User data
     * @return array Array with 'access_token' and 'refresh_token'
     */
    public function generateTokenPair(array $userData): array
    {
        $payload = [
            'user_id' => $userData['id'] ?? $userData['user_id'],
            'username' => $userData['username'] ?? null,
            'email' => $userData['email'] ?? null,
            'roles' => $userData['roles'] ?? [],
        ];

        return [
            'access_token' => $this->generate($payload, false),
            'refresh_token' => $this->generate($payload, true),
            'expires_in' => $this->expiration,
        ];
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $refreshToken Refresh token
     * @return array|false New token pair or false on failure
     */
    public function refresh(string $refreshToken): array|false
    {
        $decoded = $this->validate($refreshToken);

        if ($decoded === false) {
            return false;
        }

        // Verify it's a refresh token
        if (($decoded->type ?? 'access') !== 'refresh') {
            Logger::security('Attempted to refresh with non-refresh token');
            return false;
        }

        $userData = [
            'user_id' => $decoded->user_id,
            'username' => $decoded->username ?? null,
            'email' => $decoded->email ?? null,
            'roles' => $decoded->roles ?? [],
        ];

        return $this->generateTokenPair($userData);
    }

    /**
     * Get token from request headers
     *
     * @return string|null Token or null if not found
     */
    public function getTokenFromRequest(): ?string
    {
        // Check Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Check alternative header
        if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            return $_SERVER['HTTP_X_AUTH_TOKEN'];
        }

        // Check cookie
        if (isset($_COOKIE['jwt_token'])) {
            return $_COOKIE['jwt_token'];
        }

        return null;
    }

    /**
     * Get current decoded token
     *
     * @return object|null Current token
     */
    public function getCurrentToken(): ?object
    {
        return $this->currentToken;
    }

    /**
     * Get user ID from current token
     *
     * @return int|null User ID
     */
    public function getUserId(): ?int
    {
        return $this->currentToken->user_id ?? null;
    }

    /**
     * Get username from current token
     *
     * @return string|null Username
     */
    public function getUsername(): ?string
    {
        return $this->currentToken->username ?? null;
    }

    /**
     * Get user roles from current token
     *
     * @return array User roles
     */
    public function getRoles(): array
    {
        return $this->currentToken->roles ?? [];
    }

    /**
     * Check if current token has role
     *
     * @param string $role Role name
     * @return bool True if has role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * Check if token is expired
     *
     * @param object $token Decoded token
     * @return bool True if expired
     */
    public function isExpired(object $token): bool
    {
        $exp = $token->exp ?? 0;
        return time() >= $exp;
    }

    /**
     * Get token expiration time
     *
     * @param object $token Decoded token
     * @return int Expiration timestamp
     */
    public function getExpiration(object $token): int
    {
        return $token->exp ?? 0;
    }

    /**
     * Get remaining time until expiration
     *
     * @param object $token Decoded token
     * @return int Seconds until expiration
     */
    public function getRemainingTime(object $token): int
    {
        $exp = $this->getExpiration($token);
        return max(0, $exp - time());
    }

    /**
     * Set token in cookie
     *
     * @param string $token JWT token
     * @param bool $httpOnly HTTP only flag
     * @param bool $secure Secure flag
     * @return bool True on success
     */
    public function setTokenCookie(
        string $token,
        bool $httpOnly = true,
        bool $secure = false
    ): bool {
        $expiration = time() + $this->expiration;

        return setcookie(
            'jwt_token',
            $token,
            [
                'expires' => $expiration,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Clear token cookie
     *
     * @return bool True on success
     */
    public function clearTokenCookie(): bool
    {
        return setcookie(
            'jwt_token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
            ]
        );
    }

    /**
     * Authenticate request
     *
     * Validates token from request and sets current token.
     *
     * @return bool True if authenticated
     */
    public function authenticate(): bool
    {
        $token = $this->getTokenFromRequest();

        if ($token === null) {
            return false;
        }

        $decoded = $this->validate($token);

        if ($decoded === false) {
            return false;
        }

        // Check if it's an access token
        if (($decoded->type ?? 'access') !== 'access') {
            Logger::security('Attempted to authenticate with non-access token');
            return false;
        }

        return true;
    }

    /**
     * Get user data from current token
     *
     * @return array|null User data
     */
    public function getUserData(): ?array
    {
        if ($this->currentToken === null) {
            return null;
        }

        return [
            'user_id' => $this->currentToken->user_id ?? null,
            'username' => $this->currentToken->username ?? null,
            'email' => $this->currentToken->email ?? null,
            'roles' => $this->currentToken->roles ?? [],
        ];
    }

    /**
     * Revoke token (blacklist functionality to be implemented in Phase 2)
     *
     * @param string $token Token to revoke
     * @return bool True on success
     */
    public function revoke(string $token): bool
    {
        // TODO: Implement token blacklist in Phase 2
        Logger::auth('Token revoked', ['token' => substr($token, 0, 20) . '...']);
        return true;
    }

    /**
     * Get configuration
     *
     * @return array Configuration
     */
    public function getConfig(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'expiration' => $this->expiration,
            'refresh_expiration' => $this->refreshExpiration,
        ];
    }
}
