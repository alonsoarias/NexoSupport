<?php

declare(strict_types=1);

namespace ISER\Core\Database;

/**
 * Database Driver Detector
 *
 * Detecta drivers PDO disponibles en el sistema
 *
 * @package ISER\Core\Database
 */
class DatabaseDriverDetector
{
    /**
     * Obtener todos los drivers disponibles
     *
     * @return array Array con información de drivers disponibles
     */
    public static function getAvailableDrivers(): array
    {
        $drivers = [];
        $pdoDrivers = \PDO::getAvailableDrivers();

        // MySQL / MariaDB
        if (in_array('mysql', $pdoDrivers)) {
            $drivers['mysql'] = [
                'name' => 'MySQL / MariaDB',
                'driver' => 'mysql',
                'default_port' => 3306,
                'description' => 'MySQL 5.7+ o MariaDB 10.3+',
                'icon' => 'database',
                'color' => 'primary'
            ];
        }

        // PostgreSQL
        if (in_array('pgsql', $pdoDrivers)) {
            $drivers['pgsql'] = [
                'name' => 'PostgreSQL',
                'driver' => 'pgsql',
                'default_port' => 5432,
                'description' => 'PostgreSQL 12+',
                'icon' => 'server',
                'color' => 'info'
            ];
        }

        // SQLite (para desarrollo/testing)
        if (in_array('sqlite', $pdoDrivers)) {
            $drivers['sqlite'] = [
                'name' => 'SQLite',
                'driver' => 'sqlite',
                'default_port' => null,
                'description' => 'SQLite 3+ (solo desarrollo)',
                'icon' => 'file-earmark-text',
                'color' => 'secondary'
            ];
        }

        return $drivers;
    }

    /**
     * Verificar si un driver específico está disponible
     *
     * @param string $driver Nombre del driver (mysql, pgsql, sqlite)
     * @return bool
     */
    public static function isDriverAvailable(string $driver): bool
    {
        return in_array($driver, \PDO::getAvailableDrivers());
    }

    /**
     * Obtener el driver recomendado
     *
     * @return string|null
     */
    public static function getRecommendedDriver(): ?string
    {
        $drivers = self::getAvailableDrivers();

        // Preferencia: MySQL > PostgreSQL > SQLite
        if (isset($drivers['mysql'])) {
            return 'mysql';
        }
        if (isset($drivers['pgsql'])) {
            return 'pgsql';
        }
        if (isset($drivers['sqlite'])) {
            return 'sqlite';
        }

        return null;
    }

    /**
     * Construir DSN según el driver
     *
     * @param string $driver Driver (mysql, pgsql, sqlite)
     * @param array $config Configuración de conexión
     * @return string DSN
     */
    public static function buildDSN(string $driver, array $config): string
    {
        switch ($driver) {
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 3306,
                    $config['database'] ?? ''
                );

            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 5432,
                    $config['database'] ?? ''
                );

            case 'sqlite':
                return sprintf(
                    'sqlite:%s',
                    $config['path'] ?? ':memory:'
                );

            default:
                throw new \Exception("Driver no soportado: {$driver}");
        }
    }

    /**
     * Obtener información detallada de un driver
     *
     * @param string $driver
     * @return array|null
     */
    public static function getDriverInfo(string $driver): ?array
    {
        $drivers = self::getAvailableDrivers();
        return $drivers[$driver] ?? null;
    }
}
