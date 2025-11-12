# Hello World Tool Plugin

Example plugin demonstrating the NexoSupport plugin system.

## Description

This is a simple demonstration plugin that shows developers how to create their own plugins for NexoSupport. It implements all required `PluginInterface` methods and demonstrates best practices for plugin development.

## Features

- ✅ Implements complete `PluginInterface`
- ✅ Demonstrates hook registration
- ✅ Shows configuration schema usage
- ✅ Includes example assets (CSS/JS)
- ✅ Provides internationalization support
- ✅ Follows PSR-4 autoloading standards

## Installation

1. Upload this plugin directory to `/modules/plugins/tools/hello-world/`
2. Navigate to Admin > Plugins
3. Click "Discover Plugins" to detect the new plugin
4. Click "Install" on the Hello World Tool plugin
5. Click "Enable" to activate the plugin

## Directory Structure

```
hello-world/
├── src/
│   └── Plugin.php          # Main plugin class
├── assets/
│   ├── css/
│   │   └── hello-world.css # Plugin styles
│   └── js/
│       └── hello-world.js  # Plugin scripts
├── views/
│   └── index.mustache      # Plugin views (optional)
├── lang/
│   ├── es/
│   │   └── hello-world.php # Spanish translations
│   └── en/
│       └── hello-world.php # English translations
├── plugin.json             # Plugin manifest (required)
└── README.md               # This file
```

## Configuration

This plugin can be configured via Admin > Plugins > Hello World > Configure.

Available settings:
- **Greeting Message**: The message to display (default: "Hello, World!")
- **Show Icon**: Display an icon next to the greeting (default: true)
- **Icon Color**: Color of the icon (options: green, blue, red, yellow)

## Hooks

This plugin registers the following hooks:

- `admin.tools.menu` - Adds menu item to admin tools menu

## Permissions

This plugin defines the following permissions:

- `tools.helloworld.view` - View Hello World tool
- `tools.helloworld.configure` - Configure Hello World tool

## Development

### Requirements

- NexoSupport 1.0.0 or higher
- PHP 8.1 or higher

### Creating Your Own Plugin

Use this plugin as a template:

1. Copy the directory structure
2. Update `plugin.json` with your plugin details
3. Rename namespace in `Plugin.php`
4. Implement the required methods:
   - `install()` - Database setup, create tables
   - `uninstall()` - Cleanup, drop tables
   - `activate()` - Register hooks, start services
   - `deactivate()` - Unregister hooks, stop services
   - `update()` - Handle version migrations
   - `getInfo()` - Return plugin metadata
   - `getConfigSchema()` - Define configuration options
   - `checkDependencies()` - Validate requirements

### Testing

To test your plugin:

1. Install and enable the plugin
2. Check logs in Admin > System Logs for any errors
3. Test all configuration options
4. Test with different user roles/permissions
5. Test uninstallation and cleanup

## Support

For questions or issues:
- Documentation: https://docs.nexosupport.com/plugins
- GitHub: https://github.com/nexosupport/nexosupport
- Forum: https://community.nexosupport.com

## License

MIT License - Free to use and modify for your projects.

## Author

**NexoSupport Team**
- Website: https://nexosupport.com
- Email: plugins@nexosupport.com

## Version History

### 1.0.0 (2025-11-12)
- Initial release
- Basic functionality implementation
- Example configuration schema
- Hook registration example
