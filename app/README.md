# App Directory - Legacy Files

This directory contains legacy application files that were moved out of `public_html/` for better security.

## Structure

```
app/
├── Admin/          # Legacy admin panel files
│   ├── admin.php       # Main admin panel
│   ├── plugins.php     # Plugin management
│   ├── settings.php    # Settings management
│   └── security-check.php  # Common security verification
├── Report/         # Reporting and logs system
│   └── index.php       # Reports dashboard
└── Theme/          # Theme management
    └── index.php       # Theme customization
```

## Why Files Are Here

These files were moved from `public_html/` to reduce exposure and improve security:

- **Before**: Files were directly accessible via web browser
- **After**: Files are included via the router and require authentication

## How It Works

1. User requests a URL like `/admin`
2. Router in `public_html/index.php` handles the request
3. Router checks authentication
4. If authenticated, includes the appropriate file from `app/`
5. File content is captured and returned as response

## Security Benefits

- ✅ Files cannot be accessed directly via URL
- ✅ All requests go through central authentication
- ✅ Consistent security headers and session management
- ✅ Reduced attack surface in web root

## Future Migration

These legacy files should eventually be refactored into proper Controllers:
- Convert to PSR-7/PSR-15 compliant classes
- Use dependency injection
- Implement proper MVC pattern
- Move business logic to services

Until then, this structure maintains security while preserving functionality.
