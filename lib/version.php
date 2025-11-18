<?php
/**
 * Versión del core de NexoSupport
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();

// Información del core
$plugin->version  = 2025011805;      // YYYYMMDDXX - v1.1.5: nexoform + Core Functions
$plugin->release  = '1.1.5';         // Versión semántica
$plugin->maturity = MATURITY_STABLE; // Nivel de madurez
$plugin->component = 'core';         // Componente

// Dependencias
$plugin->dependencies = [];
