<?php
/**
 * ISER MFA System - Factor Interface
 *
 * @package    ISER\Modules\Admin\Tool\Mfa\Factors
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Modules\Admin\Tool\Mfa\Factors;

interface MfaFactorInterface
{
    /**
     * Get factor name
     *
     * @return string Factor unique identifier
     */
    public function getName(): string;

    /**
     * Get factor display name
     *
     * @return string Human-readable name
     */
    public function getDisplayName(): string;

    /**
     * Get factor description
     *
     * @return string Description of the factor
     */
    public function getDescription(): string;

    /**
     * Check if factor is configured for user
     *
     * @param int $userId User ID
     * @return bool True if configured
     */
    public function isConfigured(int $userId): bool;

    /**
     * Setup factor for user
     *
     * @param int $userId User ID
     * @param array $data Setup data
     * @return array Result with 'success' and data or 'error'
     */
    public function setup(int $userId, array $data = []): array;

    /**
     * Verify factor code/token
     *
     * @param int $userId User ID
     * @param string $code Code to verify
     * @return bool True if code is valid
     */
    public function verify(int $userId, string $code): bool;

    /**
     * Get setup template name
     *
     * @return string Template name for setup
     */
    public function getSetupTemplate(): string;

    /**
     * Get verify template name
     *
     * @return string Template name for verification
     */
    public function getVerifyTemplate(): string;

    /**
     * Get setup data for template
     *
     * @param int $userId User ID
     * @return array Data for setup template
     */
    public function getSetupData(int $userId): array;

    /**
     * Get verify data for template
     *
     * @param int $userId User ID
     * @return array Data for verify template
     */
    public function getVerifyData(int $userId): array;

    /**
     * Revoke/remove factor for user
     *
     * @param int $userId User ID
     * @return bool True on success
     */
    public function revoke(int $userId): bool;

    /**
     * Get factor configuration for user
     *
     * @param int $userId User ID
     * @return array|false Configuration data or false if not configured
     */
    public function getConfig(int $userId): array|false;

    /**
     * Check if factor can be used as primary
     *
     * @return bool True if can be primary
     */
    public function canBePrimary(): bool;

    /**
     * Get factor sort order
     *
     * @return int Sort order (lower = higher priority)
     */
    public function getSortOrder(): int;
}
