<?php
/**
 * Setup de NexoSupport
 *
 * Inicializa el sistema, variables globales y autoload.
 * IMPORTANTE: Siempre incluir PARAMS
 *
 * @package NexoSupport
 */

// Verificar que se ha definido la constante de seguridad
defined('NEXOSUPPORT_INTERNAL') || die();

// ============================================
// DEBUG LEVEL CONSTANTS
// ============================================

/* ... las definiciones previas ... */

// ============================================
// PASO 1: Cargar funciones y parámetros
// ============================================
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/authlib.php');
require_once(__DIR__ . '/userlib.php');
require_once(__DIR__ . '/params.php'); // <--- SIEMPRE INCLUIR
// ...
// resto del archivo igual ...