# Test Plugin Config

**Version:** 1.5.0
**Type:** Tool
**Status:** Test Plugin
**Configuration:** Advanced

## Purpose

Comprehensive test plugin demonstrating NexoSupport's configuration system with all supported field types and validation rules.

## Configuration Fields

This plugin demonstrates **12 different configuration field types**:

### 1. String (with validation)
- **Field:** `api_key`
- **Type:** string
- **Validation:** Required, 32-128 characters, alphanumeric only
- **Pattern:** `^[A-Za-z0-9_-]+$`

### 2. URL
- **Field:** `api_endpoint`
- **Type:** url
- **Validation:** Required, valid URL format
- **Default:** `https://api.example.com`

### 3. Email
- **Field:** `admin_email`
- **Type:** email
- **Validation:** Required, valid email format

### 4. Boolean
- **Field:** `enable_feature`
- **Type:** bool
- **Default:** `true`

### 5. Checkbox
- **Field:** `enable_logging`
- **Type:** checkbox
- **Default:** `false`

### 6. Integer (with range)
- **Field:** `max_items`
- **Type:** int
- **Validation:** Min: 1, Max: 100
- **Default:** `10`

### 7. Number (with range)
- **Field:** `cache_timeout`
- **Type:** number
- **Validation:** Min: 60, Max: 86400
- **Default:** `3600`

### 8. Select Dropdown
- **Field:** `theme_color`
- **Type:** select
- **Options:** blue, red, green, purple, orange
- **Default:** `blue`

### 9. Radio Buttons
- **Field:** `notification_type`
- **Type:** radio
- **Options:** email, sms, webhook
- **Default:** `email`

### 10. Textarea
- **Field:** `custom_css`
- **Type:** textarea
- **Validation:** Optional, Max: 5000 characters
- **Rows:** 6

### 11. Text
- **Field:** `welcome_message`
- **Type:** text
- **Validation:** Max: 200 characters
- **Default:** "Welcome to Test Plugin Config!"

### 12. Password
- **Field:** `api_secret`
- **Type:** password
- **Validation:** Optional, 16-64 characters

## Features Demonstrated

### Form Generation
- ✅ Dynamic HTML form from `config_schema`
- ✅ Bootstrap-compatible styling
- ✅ Labels, descriptions, placeholders
- ✅ Default values

### Validation
- ✅ Required field validation
- ✅ Type validation (string, int, bool, email, url)
- ✅ Range validation (min/max for numbers and strings)
- ✅ Pattern validation (regex)
- ✅ Options validation (select/radio)
- ✅ Client-side JavaScript validation
- ✅ Server-side PHP validation

### Configuration Storage
- ✅ Save to `plugin_config` table
- ✅ Load from database with defaults
- ✅ Reset to defaults
- ✅ Serialize/unserialize values

### Runtime Usage
- ✅ Access config in plugin code
- ✅ Conditional feature activation
- ✅ Validation on plugin init

## Installation

1. Install via ZIP upload at `/admin/plugins/upload`
2. Activate the plugin
3. Navigate to `/admin/plugins/test-plugin-config/configure`
4. Fill in required fields:
   - API Key (32-128 alphanumeric characters)
   - API Endpoint URL
   - Administrator Email
5. Configure optional settings
6. Save configuration

## Testing Scenarios

### Form Generation
1. **View configuration form**
   - URL: `/admin/plugins/test-plugin-config/configure`
   - Should display all 12 fields with proper types
   - Should show labels, descriptions, placeholders

### Client-Side Validation
2. **Submit empty required fields**
   - Should show "This field is required" errors
   - Should not submit form

3. **Test field-specific validation**
   - Email: Enter "invalid-email" → Should error
   - URL: Enter "not-a-url" → Should error
   - Integer: Enter "text" → Should error
   - Min/Max: Enter "0" for max_items → Should error (min: 1)

### Server-Side Validation
4. **Bypass client validation, submit invalid data**
   - Should return validation errors in JSON
   - Should display errors next to fields

### Configuration Persistence
5. **Save valid configuration**
   - Should save to database
   - Should show success message
   - Should persist after page reload

6. **Reset to defaults**
   - Click "Reset to Defaults"
   - Should restore default values
   - Should clear required fields (except defaults)

### Runtime Configuration Usage
7. **Check plugin uses configuration**
   - Enable/disable features via config
   - Verify plugin behavior changes
   - Check `TestPluginConfig::getStatus()`

## API Endpoints

- `GET /admin/plugins/test-plugin-config/configure` - Configuration form
- `POST /admin/plugins/test-plugin-config/configure` - Save configuration
- `GET /admin/plugins/test-plugin-config/config` - Get current config (JSON)
- `POST /admin/plugins/test-plugin-config/configure/reset` - Reset to defaults

## Configuration Schema Reference

All field types are defined in `plugin.json` under `config_schema`:

```json
{
  "field_name": {
    "type": "string|int|bool|select|...",
    "required": true|false,
    "label": "Display Label",
    "description": "Help text",
    "default": "default value",
    "min": 1,
    "max": 100,
    "pattern": "^regex$",
    "options": ["opt1", "opt2"],
    "placeholder": "Enter value...",
    "rows": 6
  }
}
```

## Development Notes

This plugin is the **definitive reference** for:
- Configuration schema design
- Field type usage
- Validation rule syntax
- Form customization
- Configuration access patterns
- Error handling

Use this as a template when creating plugins that need configuration.
