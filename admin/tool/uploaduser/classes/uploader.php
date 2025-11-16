<?php
/**
 * NexoSupport - User Uploader Class
 *
 * Handles CSV file processing and bulk user creation
 *
 * @package    tool_uploaduser
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace tool_uploaduser;

use ISER\Core\Database\Database;
use core\user\user_helper;

/**
 * User Uploader - Process CSV files and create users in bulk
 */
class uploader
{
    private Database $db;
    private user_helper $userHelper;

    private array $requiredColumns = ['username', 'email', 'password'];
    private array $optionalColumns = ['firstname', 'lastname', 'status'];

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userHelper = new user_helper($db);
    }

    /**
     * Process CSV file and create users
     *
     * @param string $filepath Path to CSV file
     * @param string $filename Original filename
     * @return array Results with success/error counts and details
     */
    public function process_file(string $filepath, string $filename): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => [],
        ];

        // Validate file extension
        if (!$this->is_valid_extension($filename)) {
            throw new \Exception('Invalid file type. Only CSV files are allowed.');
        }

        // Open file
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new \Exception('Unable to open file');
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new \Exception('Empty CSV file');
        }

        // Normalize headers (trim, lowercase)
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        // Validate headers
        $this->validate_headers($headers);

        // Process each row
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Combine headers with row data
            $userData = array_combine($headers, $row);

            // Process user
            $result = $this->process_user($userData, $rowNumber);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
            }

            $results['details'][] = $result;
        }

        fclose($handle);

        return $results;
    }

    /**
     * Process single user from CSV row
     *
     * @param array $userData User data from CSV
     * @param int $rowNumber Row number for error reporting
     * @return array Result with success flag and message
     */
    private function process_user(array $userData, int $rowNumber): array
    {
        $result = [
            'success' => false,
            'username' => $userData['username'] ?? '',
            'email' => $userData['email'] ?? '',
            'message' => '',
        ];

        try {
            // Trim all values
            $userData = array_map('trim', $userData);

            // Validate data
            $errors = $this->validate_user_data($userData);
            if (!empty($errors)) {
                $result['message'] = 'Row ' . $rowNumber . ': ' . implode(', ', $errors);
                return $result;
            }

            // Check if username exists
            if ($this->userHelper->username_exists($userData['username'])) {
                $result['message'] = 'Row ' . $rowNumber . ': Username already exists';
                return $result;
            }

            // Check if email exists
            if ($this->userHelper->email_exists($userData['email'])) {
                $result['message'] = 'Row ' . $rowNumber . ': Email already exists';
                return $result;
            }

            // Prepare user data
            $userToCreate = [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => $userData['password'], // Will be hashed by user_helper
                'firstname' => $userData['firstname'] ?? '',
                'lastname' => $userData['lastname'] ?? '',
                'status' => $userData['status'] ?? 'active',
                'emailverified' => false,
                'created_at' => time(),
            ];

            // Create user
            $userId = $this->userHelper->create_user($userToCreate);

            if ($userId) {
                $result['success'] = true;
                $result['message'] = 'User created successfully (ID: ' . $userId . ')';
            } else {
                $result['message'] = 'Row ' . $rowNumber . ': Failed to create user';
            }
        } catch (\Exception $e) {
            $result['message'] = 'Row ' . $rowNumber . ': ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate CSV headers
     *
     * @param array $headers CSV headers
     * @throws \Exception If required headers are missing
     */
    private function validate_headers(array $headers): void
    {
        $missingRequired = [];

        foreach ($this->requiredColumns as $required) {
            if (!in_array($required, $headers)) {
                $missingRequired[] = $required;
            }
        }

        if (!empty($missingRequired)) {
            throw new \Exception(
                'Missing required columns: ' . implode(', ', $missingRequired)
            );
        }
    }

    /**
     * Validate user data
     *
     * @param array $data User data
     * @return array Validation errors
     */
    private function validate_user_data(array $data): array
    {
        $errors = [];

        // Username validation
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        // Status validation
        if (isset($data['status']) && !in_array($data['status'], ['active', 'suspended', 'pending'])) {
            $errors[] = 'Invalid status. Must be: active, suspended, or pending';
        }

        return $errors;
    }

    /**
     * Check if file extension is valid
     *
     * @param string $filename Filename
     * @return bool True if valid
     */
    private function is_valid_extension(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ['csv', 'txt']);
    }

    /**
     * Get supported file extensions
     *
     * @return array Supported extensions
     */
    public function get_supported_extensions(): array
    {
        return ['csv', 'txt'];
    }
}
