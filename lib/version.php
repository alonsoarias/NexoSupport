<?php
/**
 * Versión del core de NexoSupport
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$plugin = new stdClass();

// Información del core
$plugin->version  = 2025011827;      // YYYYMMDDXX - v1.1.27: Fix Mustache namespace compatibility
$plugin->release  = '1.1.27';        // Versión semántica
$plugin->maturity = MATURITY_STABLE; // Nivel de madurez
$plugin->component = 'core';         // Componente

// Dependencias
$plugin->dependencies = [];
