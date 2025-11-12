<?php
/**
 * ISER - Upload User Tool
 *
 * Allows bulk user import from CSV files.
 * Validates data, creates users, and provides detailed reports.
 *
 * @package    ISER\Modules\Admin\Tool\UploadUser
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Admin\Tool\UploadUser;

use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\Core\Utils\Logger;

class UploadUser
{
    private Database $db;
    private UserManager $userManager;

    // Required CSV columns
    private const REQUIRED_COLUMNS = ['username', 'email', 'password', 'firstname', 'lastname'];

    // Optional CSV columns
    private const OPTIONAL_COLUMNS = ['phone', 'address', 'city', 'country', 'institution'];

    public function __construct(Database $db, UserManager $userManager)
    {
        $this->db = $db;
        $this->userManager = $userManager;
    }

    /**
     * Get CSV template
     *
     * @return string CSV template content
     */
    public function getTemplate(): string
    {
        $headers = array_merge(self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS);

        $csv = implode(',', $headers) . "\n";
        $csv .= "john_doe,john@example.com,SecurePass123!,John,Doe,555-0100,123 Main St,New York,USA,ACME Corp\n";
        $csv .= "jane_smith,jane@example.com,SecurePass456!,Jane,Smith,555-0200,456 Oak Ave,Los Angeles,USA,Tech Inc\n";

        return $csv;
    }

    /**
     * Validate CSV file
     *
     * @param string $filePath Path to CSV file
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public function validateCsv(string $filePath): array
    {
        $errors = [];

        // Check file exists
        if (!file_exists($filePath)) {
            return ['valid' => false, 'errors' => ['Archivo no encontrado']];
        }

        // Check file size (max 5MB)
        if (filesize($filePath) > 5 * 1024 * 1024) {
            return ['valid' => false, 'errors' => ['Archivo demasiado grande (máx 5MB)']];
        }

        // Open and read CSV
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['valid' => false, 'errors' => ['No se pudo abrir el archivo']];
        }

        // Get headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['valid' => false, 'errors' => ['Archivo CSV vacío']];
        }

        // Validate headers
        $missingColumns = array_diff(self::REQUIRED_COLUMNS, $headers);
        if (!empty($missingColumns)) {
            fclose($handle);
            return [
                'valid' => false,
                'errors' => ['Columnas requeridas faltantes: ' . implode(', ', $missingColumns)]
            ];
        }

        // Validate rows
        $rowNumber = 1;
        $rowCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $rowCount++;

            // Combine headers with row data
            $data = array_combine($headers, $row);

            // Validate required fields
            foreach (self::REQUIRED_COLUMNS as $column) {
                if (empty($data[$column])) {
                    $errors[] = "Fila {$rowNumber}: Campo '{$column}' vacío";
                }
            }

            // Validate email format
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Fila {$rowNumber}: Email inválido '{$data['email']}'";
            }

            // Validate username format (alphanumeric and underscore)
            if (!empty($data['username']) && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
                $errors[] = "Fila {$rowNumber}: Username inválido '{$data['username']}' (3-30 caracteres alfanuméricos)";
            }

            // Validate password strength (min 8 chars)
            if (!empty($data['password']) && strlen($data['password']) < 8) {
                $errors[] = "Fila {$rowNumber}: Contraseña demasiado corta (mín 8 caracteres)";
            }
        }

        fclose($handle);

        if (empty($rowCount)) {
            return ['valid' => false, 'errors' => ['No hay datos en el archivo CSV']];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'row_count' => $rowCount
        ];
    }

    /**
     * Process CSV file and create users
     *
     * @param string $filePath Path to CSV file
     * @param bool $updateExisting Update existing users
     * @param bool $sendEmails Send welcome emails
     * @return array Processing result with statistics
     */
    public function processCsv(string $filePath, bool $updateExisting = false, bool $sendEmails = false): array
    {
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Validate first
        $validation = $this->validateCsv($filePath);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'stats' => $stats,
                'errors' => $validation['errors']
            ];
        }

        // Open CSV
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $stats['total']++;

            // Combine headers with row data
            $data = array_combine($headers, $row);

            try {
                // Check if user exists
                $existingUser = $this->userManager->getUserByUsername($data['username']);

                if ($existingUser) {
                    if ($updateExisting) {
                        // Update existing user
                        $updateData = [
                            'email' => $data['email'],
                            'firstname' => $data['firstname'],
                            'lastname' => $data['lastname'],
                        ];

                        // Only update password if provided
                        if (!empty($data['password'])) {
                            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                        }

                        if ($this->userManager->updateUser($existingUser['id'], $updateData)) {
                            $stats['updated']++;

                            // Update profile if optional fields present
                            $this->updateUserProfile($existingUser['id'], $data);
                        } else {
                            $stats['errors'][] = "Fila {$rowNumber}: Error al actualizar usuario '{$data['username']}'";
                        }
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    // Create new user
                    $userData = [
                        'username' => $data['username'],
                        'email' => $data['email'],
                        'password' => $data['password'],
                        'firstname' => $data['firstname'],
                        'lastname' => $data['lastname'],
                        'status' => 1
                    ];

                    $userId = $this->userManager->createUser($userData);

                    if ($userId) {
                        $stats['created']++;

                        // Create profile if optional fields present
                        $this->updateUserProfile($userId, $data);

                        // Send welcome email if requested
                        if ($sendEmails) {
                            $this->sendWelcomeEmail($data['email'], $data['first_name'], $data['username'], $tempPassword);
                        }

                        Logger::auth('User created via CSV upload', [
                            'userid' => $userId,
                            'username' => $data['username']
                        ]);
                    } else {
                        $stats['errors'][] = "Fila {$rowNumber}: Error al crear usuario '{$data['username']}'";
                    }
                }
            } catch (\Exception $e) {
                $stats['errors'][] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Update user profile with optional CSV data
     *
     * @param int $userId User ID
     * @param array $data CSV row data
     * @return bool True on success
     */
    private function updateUserProfile(int $userId, array $data): bool
    {
        $profileData = [];

        foreach (self::OPTIONAL_COLUMNS as $column) {
            if (!empty($data[$column])) {
                $profileData[$column] = $data[$column];
            }
        }

        if (empty($profileData)) {
            return true; // No profile data to update
        }

        // Check if profile exists
        $existingProfile = $this->db->selectOne('user_profiles', ['userid' => $userId]);

        if ($existingProfile) {
            // Update existing profile
            return $this->db->update('user_profiles', array_merge($profileData, [
                'timemodified' => time()
            ]), ['userid' => $userId]) > 0;
        } else {
            // Create new profile
            return $this->db->insert('user_profiles', array_merge($profileData, [
                'userid' => $userId,
                'timecreated' => time(),
                'timemodified' => time()
            ])) !== false;
        }
    }

    /**
     * Get upload statistics
     *
     * @return array Upload statistics
     */
    public function getUploadStats(): array
    {
        // Get recent uploads from admin log
        $sql = "SELECT COUNT(*) as count, MAX(timecreated) as last_upload
                FROM {$this->db->table('admin_log')}
                WHERE action = 'user_upload_csv'";

        try {
            $result = $this->db->getConnection()->fetchOne($sql);
            return [
                'total_uploads' => (int)($result['count'] ?? 0),
                'last_upload' => (int)($result['last_upload'] ?? 0)
            ];
        } catch (\Exception $e) {
            return ['total_uploads' => 0, 'last_upload' => 0];
        }
    }

    /**
     * Send welcome email to newly created user
     *
     * @param string $email User email address
     * @param string $firstName User first name
     * @param string $username User username
     * @param string $tempPassword Temporary password
     * @return void
     */
    private function sendWelcomeEmail(string $email, string $firstName, string $username, string $tempPassword): void
    {
        try {
            // Prepare email content
            $subject = 'Bienvenido a NexoSupport - Credenciales de Acceso';
            $message = "Hola {$firstName},\n\n";
            $message .= "Tu cuenta en NexoSupport ha sido creada exitosamente.\n\n";
            $message .= "Credenciales de acceso:\n";
            $message .= "Usuario: {$username}\n";
            $message .= "Contraseña temporal: {$tempPassword}\n\n";
            $message .= "Por favor, cambia tu contraseña después del primer inicio de sesión.\n\n";
            $message .= "Puedes acceder al sistema en: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\n\n";
            $message .= "Saludos,\n";
            $message .= "Equipo de NexoSupport";

            // Log the email (actual sending will be implemented when email system is configured)
            Logger::info('Welcome email prepared for new user', [
                'email' => $email,
                'username' => $username,
                'subject' => $subject
            ]);

            // TODO: When email system is fully configured, replace logging with actual email sending:
            // $mailer = new Mailer();
            // $mailer->send($email, $subject, $message);

            // For now, we log the email content so admins can manually send it if needed
            Logger::debug('Email content', [
                'to' => $email,
                'subject' => $subject,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to send welcome email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception - email failure shouldn't stop user creation
        }
    }
}
