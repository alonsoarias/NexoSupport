# Test Plugin B

**Version:** 1.0.0
**Type:** Tool
**Status:** Test Plugin

## Purpose

Base dependency plugin used for testing the NexoSupport plugin system's dependency resolution capabilities.

## Features

- Provides core functionality for dependent plugins
- Exposes global helper functions
- Demonstrates basic plugin structure
- Used as a dependency by Test Plugin A

## Global Functions

This plugin registers the following global functions:

- `test_plugin_b_get_version()` - Returns the plugin version
- `test_plugin_b_is_active()` - Returns true if plugin is active

## Dependencies

- PHP >= 8.0
- NexoSupport >= 1.0.0

## Usage in Tests

This plugin is used to test:
- Basic plugin installation
- Plugin as a dependency for other plugins
- Dependency resolution and ordering
- Activation/deactivation with dependent plugins

## Installation

Install via ZIP upload in the admin panel under `/admin/plugins/upload`.

## Testing Scenarios

1. **Install Test Plugin B first** - Should install without issues
2. **Install Test Plugin A** - Should auto-install Test Plugin B if not present
3. **Try to uninstall Test Plugin B while A is active** - Should fail with dependency warning
4. **Disable Test Plugin A, then uninstall B** - Should succeed
