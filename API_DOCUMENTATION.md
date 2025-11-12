# NexoSupport API Documentation

**Version:** 1.0.0
**Last Updated:** 2025-11-12
**Base URL:** `http://yourdomain.com` or `https://yourdomain.com`

---

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Error Handling](#error-handling)
5. [Public Endpoints](#public-endpoints)
6. [User Endpoints](#user-endpoints)
7. [Admin Endpoints](#admin-endpoints)
   - [Settings](#admin-settings)
   - [Users](#admin-users)
   - [Roles](#admin-roles)
   - [Permissions](#admin-permissions)
   - [Appearance](#admin-appearance)
   - [Plugins](#admin-plugins)
   - [Logs](#admin-logs)
   - [Audit](#admin-audit)
   - [Email Queue](#admin-email-queue)
   - [Backups](#admin-backups)
8. [API Endpoints](#api-endpoints)
9. [Webhooks](#webhooks)

---

## Introduction

NexoSupport provides a comprehensive REST API for authentication, user management, and system administration. All API endpoints follow RESTful principles and return JSON responses (unless specified otherwise).

### API Features

- **RESTful Design**: Standard HTTP methods (GET, POST, PUT, DELETE)
- **JSON Responses**: All API responses in JSON format
- **Session-Based Auth**: Cookie-based session authentication
- **RBAC**: Role-based access control with granular permissions
- **CSRF Protection**: All POST/PUT/DELETE requests require CSRF token
- **i18n Support**: Multilingual responses (ES/EN)

### Base URL

```
Production:  https://yourdomain.com
Development: http://localhost
```

---

## Authentication

NexoSupport uses **session-based authentication** with cookies.

### Login Process

1. **POST** `/login` with credentials
2. Server validates and creates session
3. Session cookie is set (`PHPSESSID`)
4. Include cookie in all subsequent requests

### Authentication Flow

```
Client                          Server
  |                               |
  |--- POST /login -------------->|
  |    {username, password}       |
  |                               |
  |<-- 200 OK --------------------|
  |    Set-Cookie: PHPSESSID=...  |
  |                               |
  |--- GET /dashboard ----------->|
  |    Cookie: PHPSESSID=...      |
  |                               |
  |<-- 200 OK --------------------|
  |    {dashboard data}           |
```

### CSRF Protection

All state-changing requests (POST, PUT, DELETE) require a CSRF token:

```html
<form method="POST">
    <input type="hidden" name="csrf_token" value="{{csrf_token}}">
    <!-- form fields -->
</form>
```

**JavaScript Example**:
```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        csrf_token: document.querySelector('[name=csrf_token]').value,
        data: {...}
    })
});
```

---

## Response Format

### Success Response

**HTTP 200 OK**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Example"
  },
  "message": "Operation completed successfully"
}
```

### Error Response

**HTTP 4xx/5xx**

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Pagination

For list endpoints:

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  }
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| `200` | OK | Request successful |
| `201` | Created | Resource created successfully |
| `204` | No Content | Request successful, no response body |
| `400` | Bad Request | Invalid input data |
| `401` | Unauthorized | Not authenticated |
| `403` | Forbidden | Authenticated but insufficient permissions |
| `404` | Not Found | Resource not found |
| `422` | Unprocessable Entity | Validation failed |
| `500` | Internal Server Error | Server error |
| `503` | Service Unavailable | System maintenance |

### Error Response Examples

**Validation Error (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["Email is required", "Email format is invalid"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

**Permission Error (403)**:
```json
{
  "success": false,
  "message": "You do not have permission to perform this action",
  "required_permission": "admin.users.delete"
}
```

**Not Found (404)**:
```json
{
  "success": false,
  "message": "User not found",
  "resource": "user",
  "id": 123
}
```

---

## Public Endpoints

### Home Page

**GET** `/`

Display the home page.

**Response**: HTML page

---

### Login

#### Show Login Form

**GET** `/login`

Display the login form.

**Response**: HTML page

---

#### Process Login

**POST** `/login`

Authenticate user and create session.

**Request Body**:
```json
{
  "username": "john.doe",
  "password": "password123",
  "remember": true
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Login successful",
  "redirect": "/dashboard"
}
```

**Error Response (401)**:
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### Logout

**GET** `/logout`

Destroy user session and logout.

**Response**: Redirect to `/login`

---

### Password Reset

#### Request Password Reset

**GET** `/forgot-password`

Display password reset request form.

**Response**: HTML page

---

**POST** `/forgot-password`

Send password reset email.

**Request Body**:
```json
{
  "email": "user@example.com"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

---

#### Reset Password

**GET** `/reset-password?token={token}`

Display password reset form.

**Query Parameters**:
- `token` (string, required): Password reset token from email

**Response**: HTML page

---

**POST** `/reset-password`

Reset user password with token.

**Request Body**:
```json
{
  "token": "abc123def456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

## User Endpoints

### Dashboard

**GET** `/dashboard`

**Auth Required**: Yes

Display user dashboard.

**Response**: HTML page with dashboard stats and widgets

---

### User Profile

#### View Own Profile

**GET** `/profile`

**Auth Required**: Yes

Display logged-in user's profile.

**Response**: HTML page

---

#### View User Profile

**GET** `/profile/view/{id}`

**Auth Required**: Yes
**Permission**: `users.view`

View another user's profile.

**Path Parameters**:
- `id` (integer, required): User ID

**Response**: HTML page

---

#### Edit Profile

**GET** `/profile/edit`

**Auth Required**: Yes

Display profile edit form.

**Response**: HTML page

---

**POST** `/profile/edit`

**Auth Required**: Yes

Update user profile.

**Request Body**:
```json
{
  "csrf_token": "...",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "bio": "Software developer"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Profile updated successfully"
}
```

---

### User Preferences

#### View Preferences

**GET** `/preferences`

**Auth Required**: Yes

Display user preferences form.

**Response**: HTML page

---

#### Update Preferences

**POST** `/preferences`

**Auth Required**: Yes

Update user preferences.

**Request Body**:
```json
{
  "csrf_token": "...",
  "language": "es",
  "timezone": "America/Mexico_City",
  "theme": "light",
  "notifications": {
    "email": true,
    "browser": false
  }
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Preferences updated successfully"
}
```

---

### Login History

#### View Login History

**GET** `/login-history`

**Auth Required**: Yes

Display user's login history.

**Response**: HTML page with login sessions

---

#### Terminate Session

**POST** `/login-history/terminate/{id}`

**Auth Required**: Yes

Terminate a specific login session.

**Path Parameters**:
- `id` (integer, required): Session ID

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Session terminated successfully"
}
```

---

## Admin Endpoints

All admin endpoints require authentication and appropriate permissions.

### Admin Settings

#### View Settings

**GET** `/admin/settings`

**Auth Required**: Yes
**Permission**: `admin.settings.view`

Display system settings form.

**Response**: HTML page

---

#### Update Settings

**POST** `/admin/settings`

**Auth Required**: Yes
**Permission**: `admin.settings.manage`

Update system settings.

**Request Body**:
```json
{
  "csrf_token": "...",
  "site_name": "NexoSupport",
  "site_url": "https://example.com",
  "admin_email": "admin@example.com",
  "maintenance_mode": false,
  "session_lifetime": 3600,
  "max_login_attempts": 5,
  "password_min_length": 8
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Settings updated successfully"
}
```

---

#### Reset Settings

**POST** `/admin/settings/reset`

**Auth Required**: Yes
**Permission**: `admin.settings.manage`

Reset settings to defaults.

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Settings reset to defaults"
}
```

---

### Admin Users

#### List Users

**GET** `/admin/users`

**Auth Required**: Yes
**Permission**: `admin.users.view`

Display list of all users.

**Query Parameters**:
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Items per page (default: 20)
- `search` (string, optional): Search query
- `role` (string, optional): Filter by role

**Response**: HTML page with user list

---

#### Create User Form

**GET** `/admin/users/create`

**Auth Required**: Yes
**Permission**: `admin.users.create`

Display user creation form.

**Response**: HTML page

---

#### Store User

**POST** `/admin/users/store`

**Auth Required**: Yes
**Permission**: `admin.users.create`

Create a new user.

**Request Body**:
```json
{
  "csrf_token": "...",
  "username": "john.doe",
  "email": "john@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "roles": [1, 2],
  "active": true
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "id": 123,
    "username": "john.doe",
    "email": "john@example.com"
  }
}
```

---

#### Edit User Form

**POST** `/admin/users/edit`

**Auth Required**: Yes
**Permission**: `admin.users.edit`

Display user edit form.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 123
}
```

**Response**: HTML page with edit form

---

#### Update User

**POST** `/admin/users/update`

**Auth Required**: Yes
**Permission**: `admin.users.edit`

Update an existing user.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 123,
  "username": "john.doe",
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "roles": [1, 2],
  "active": true
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

---

#### Delete User

**POST** `/admin/users/delete`

**Auth Required**: Yes
**Permission**: `admin.users.delete`

Soft delete a user.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 123
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

#### Restore User

**POST** `/admin/users/restore`

**Auth Required**: Yes
**Permission**: `admin.users.delete`

Restore a soft-deleted user.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 123
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "User restored successfully"
}
```

---

### Admin Roles

#### List Roles

**GET** `/admin/roles`

**Auth Required**: Yes
**Permission**: `admin.roles.view`

Display list of all roles.

**Response**: HTML page with roles list

---

#### Create Role Form

**GET** `/admin/roles/create`

**Auth Required**: Yes
**Permission**: `admin.roles.create`

Display role creation form.

**Response**: HTML page

---

#### Store Role

**POST** `/admin/roles/store`

**Auth Required**: Yes
**Permission**: `admin.roles.create`

Create a new role.

**Request Body**:
```json
{
  "csrf_token": "...",
  "name": "Supervisor",
  "description": "Supervisor role with extended permissions",
  "permissions": [1, 2, 3, 5, 8]
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "message": "Role created successfully",
  "data": {
    "id": 5,
    "name": "Supervisor"
  }
}
```

---

#### Edit Role Form

**POST** `/admin/roles/edit`

**Auth Required**: Yes
**Permission**: `admin.roles.edit`

Display role edit form.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 5
}
```

**Response**: HTML page with edit form

---

#### Update Role

**POST** `/admin/roles/update`

**Auth Required**: Yes
**Permission**: `admin.roles.edit`

Update an existing role.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 5,
  "name": "Senior Supervisor",
  "description": "Updated description",
  "permissions": [1, 2, 3, 4, 5, 8, 13]
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Role updated successfully"
}
```

---

#### Delete Role

**POST** `/admin/roles/delete`

**Auth Required**: Yes
**Permission**: `admin.roles.delete`

Delete a role.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 5
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Role deleted successfully"
}
```

---

### Admin Permissions

#### List Permissions

**GET** `/admin/permissions`

**Auth Required**: Yes
**Permission**: `admin.permissions.view`

Display list of all permissions.

**Response**: HTML page with permissions list

---

#### Create Permission Form

**GET** `/admin/permissions/create`

**Auth Required**: Yes
**Permission**: `admin.permissions.create`

Display permission creation form.

**Response**: HTML page

---

#### Store Permission

**POST** `/admin/permissions/store`

**Auth Required**: Yes
**Permission**: `admin.permissions.create`

Create a new permission.

**Request Body**:
```json
{
  "csrf_token": "...",
  "name": "reports.export.excel",
  "description": "Export reports to Excel format",
  "category": "reports"
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "message": "Permission created successfully",
  "data": {
    "id": 42,
    "name": "reports.export.excel"
  }
}
```

---

#### Edit Permission Form

**POST** `/admin/permissions/edit`

**Auth Required**: Yes
**Permission**: `admin.permissions.edit`

Display permission edit form.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 42
}
```

**Response**: HTML page

---

#### Update Permission

**POST** `/admin/permissions/update`

**Auth Required**: Yes
**Permission**: `admin.permissions.edit`

Update an existing permission.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 42,
  "name": "reports.export.excel",
  "description": "Export reports to Microsoft Excel format (.xlsx)",
  "category": "reports"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Permission updated successfully"
}
```

---

#### Delete Permission

**POST** `/admin/permissions/delete`

**Auth Required**: Yes
**Permission**: `admin.permissions.delete`

Delete a permission.

**Request Body**:
```json
{
  "csrf_token": "...",
  "id": 42
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Permission deleted successfully"
}
```

---

### Admin Appearance

#### View Appearance Settings

**GET** `/admin/appearance`

**Auth Required**: Yes
**Permission**: `admin.appearance.manage`

Display appearance customization page.

**Response**: HTML page

---

#### Save Appearance

**POST** `/admin/appearance/save`

**Auth Required**: Yes
**Permission**: `admin.appearance.manage`

Save appearance settings.

**Request Body**:
```json
{
  "csrf_token": "...",
  "theme": "default",
  "colors": {
    "primary": "#0066cc",
    "secondary": "#6c757d",
    "success": "#28a745",
    "danger": "#dc3545"
  },
  "logo_url": "/uploads/logo.png",
  "favicon_url": "/uploads/favicon.ico"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Appearance settings saved"
}
```

---

#### Reset Appearance

**POST** `/admin/appearance/reset`

**Auth Required**: Yes
**Permission**: `admin.appearance.manage`

Reset appearance to default.

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Appearance reset to default"
}
```

---

### Admin Plugins

#### List Plugins

**GET** `/admin/plugins`

**Auth Required**: Yes
**Permission**: `admin.plugins.view`

Display list of installed plugins.

**Response**: HTML page

---

#### Discover Plugins

**POST** `/admin/plugins/discover`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Scan for new plugins in modules directory.

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Found 3 new plugins",
  "data": {
    "discovered": ["plugin1", "plugin2", "plugin3"]
  }
}
```

---

#### Upload Plugin Form

**GET** `/admin/plugins/upload`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Display plugin upload form.

**Response**: HTML page

---

#### Upload Plugin

**POST** `/admin/plugins/upload`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Upload and install plugin from ZIP file.

**Request**:
- Content-Type: `multipart/form-data`
- Field: `plugin_file` (file, required): ZIP file

**Success Response (201)**:
```json
{
  "success": true,
  "message": "Plugin installed successfully",
  "data": {
    "slug": "my-plugin",
    "name": "My Plugin",
    "version": "1.0.0"
  }
}
```

---

#### Enable Plugin

**POST** `/admin/plugins/{slug}/enable`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Enable a plugin.

**Path Parameters**:
- `slug` (string, required): Plugin slug

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Plugin enabled successfully"
}
```

---

#### Disable Plugin

**POST** `/admin/plugins/{slug}/disable`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Disable a plugin.

**Path Parameters**:
- `slug` (string, required): Plugin slug

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Plugin disabled successfully"
}
```

---

#### Uninstall Plugin

**POST** `/admin/plugins/{slug}/uninstall`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Uninstall and delete a plugin.

**Path Parameters**:
- `slug` (string, required): Plugin slug

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Plugin uninstalled successfully"
}
```

---

#### Update Plugin

**POST** `/admin/plugins/{slug}/update`

**Auth Required**: Yes
**Permission**: `admin.plugins.manage`

Update plugin to new version.

**Path Parameters**:
- `slug` (string, required): Plugin slug

**Request**:
- Content-Type: `multipart/form-data`
- Field: `plugin_file` (file, required): ZIP file with new version

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Plugin updated successfully",
  "data": {
    "old_version": "1.0.0",
    "new_version": "1.1.0"
  }
}
```

---

#### View Plugin Details

**GET** `/admin/plugins/{slug}`

**Auth Required**: Yes
**Permission**: `admin.plugins.view`

Display plugin details.

**Path Parameters**:
- `slug` (string, required): Plugin slug

**Response**: HTML page with plugin details

---

### Admin Logs

#### List Logs

**GET** `/admin/logs`

**Auth Required**: Yes
**Permission**: `admin.logs.view`

Display system logs.

**Query Parameters**:
- `level` (string, optional): Filter by log level
- `date` (string, optional): Filter by date (YYYY-MM-DD)
- `search` (string, optional): Search in messages

**Response**: HTML page with log entries

---

#### View Log Entry

**GET** `/admin/logs/view/{id}`

**Auth Required**: Yes
**Permission**: `admin.logs.view`

Display detailed log entry.

**Path Parameters**:
- `id` (integer, required): Log entry ID

**Response**: HTML page

---

#### Clear Logs

**POST** `/admin/logs/clear`

**Auth Required**: Yes
**Permission**: `admin.logs.manage`

Clear all log entries.

**Request Body**:
```json
{
  "csrf_token": "...",
  "older_than": "2024-01-01"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Logs cleared successfully",
  "data": {
    "deleted_count": 1523
  }
}
```

---

#### Download Logs

**GET** `/admin/logs/download`

**Auth Required**: Yes
**Permission**: `admin.logs.view`

Download logs as file.

**Query Parameters**:
- `format` (string, optional): `json` or `csv` (default: `json`)
- `level` (string, optional): Filter by level
- `date` (string, optional): Filter by date

**Response**: File download

---

### Admin Audit

#### List Audit Logs

**GET** `/admin/audit`

**Auth Required**: Yes
**Permission**: `admin.audit.view`

Display audit logs.

**Query Parameters**:
- `user_id` (integer, optional): Filter by user
- `action` (string, optional): Filter by action type
- `date_from` (string, optional): Start date (YYYY-MM-DD)
- `date_to` (string, optional): End date (YYYY-MM-DD)

**Response**: HTML page with audit entries

---

#### View Audit Entry

**GET** `/admin/audit/view/{id}`

**Auth Required**: Yes
**Permission**: `admin.audit.view`

Display detailed audit entry.

**Path Parameters**:
- `id` (integer, required): Audit entry ID

**Response**: HTML page

---

#### Export Audit Logs

**GET** `/admin/audit/export`

**Auth Required**: Yes
**Permission**: `admin.audit.export`

Export audit logs.

**Query Parameters**:
- `format` (string, optional): `json`, `csv`, or `pdf` (default: `csv`)
- `date_from` (string, optional): Start date
- `date_to` (string, optional): End date

**Response**: File download

---

### Admin Email Queue

#### List Email Queue

**GET** `/admin/email-queue`

**Auth Required**: Yes
**Permission**: `admin.email.view`

Display email queue.

**Query Parameters**:
- `status` (string, optional): Filter by status (pending, sent, failed)

**Response**: HTML page

---

#### View Email

**GET** `/admin/email-queue/view/{id}`

**Auth Required**: Yes
**Permission**: `admin.email.view`

Display email details.

**Path Parameters**:
- `id` (integer, required): Email ID

**Response**: HTML page

---

#### Retry Email

**POST** `/admin/email-queue/retry/{id}`

**Auth Required**: Yes
**Permission**: `admin.email.manage`

Retry sending failed email.

**Path Parameters**:
- `id` (integer, required): Email ID

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Email queued for retry"
}
```

---

#### Delete Email

**POST** `/admin/email-queue/delete/{id}`

**Auth Required**: Yes
**Permission**: `admin.email.manage`

Delete email from queue.

**Path Parameters**:
- `id` (integer, required): Email ID

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Email deleted from queue"
}
```

---

#### Clear Queue

**POST** `/admin/email-queue/clear`

**Auth Required**: Yes
**Permission**: `admin.email.manage`

Clear all sent/failed emails.

**Request Body**:
```json
{
  "csrf_token": "...",
  "status": "sent"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Email queue cleared",
  "data": {
    "deleted_count": 245
  }
}
```

---

### Admin Backups

#### List Backups

**GET** `/admin/backup`

**Auth Required**: Yes
**Permission**: `admin.backup.view`

Display list of backups.

**Response**: HTML page

---

#### Create Backup

**POST** `/admin/backup/create`

**Auth Required**: Yes
**Permission**: `admin.backup.create`

Create new database backup.

**Request Body**:
```json
{
  "csrf_token": "...",
  "include_uploads": true,
  "compression": true
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "filename": "backup_20251112_143052.sql.gz",
    "size": 5242880,
    "created_at": "2025-11-12 14:30:52"
  }
}
```

---

#### Download Backup

**GET** `/admin/backup/download/{filename}`

**Auth Required**: Yes
**Permission**: `admin.backup.download`

Download backup file.

**Path Parameters**:
- `filename` (string, required): Backup filename

**Response**: File download

---

#### Delete Backup

**POST** `/admin/backup/delete/{filename}`

**Auth Required**: Yes
**Permission**: `admin.backup.delete`

Delete a backup file.

**Path Parameters**:
- `filename` (string, required): Backup filename

**Request Body**:
```json
{
  "csrf_token": "..."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Backup deleted successfully"
}
```

---

## API Endpoints

### Search

#### Search Suggestions

**GET** `/api/search/suggestions`

**Auth Required**: Yes

Get search suggestions as user types.

**Query Parameters**:
- `q` (string, required): Search query
- `limit` (integer, optional): Max results (default: 10)

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "type": "user",
      "id": 123,
      "title": "John Doe",
      "subtitle": "john.doe@example.com",
      "url": "/profile/view/123"
    },
    {
      "type": "plugin",
      "id": 5,
      "title": "My Plugin",
      "subtitle": "Tool plugin v1.0.0",
      "url": "/admin/plugins/my-plugin"
    }
  ]
}
```

---

### Internationalization (i18n)

#### Get Current Locale

**GET** `/api/i18n/current`

Get current locale setting.

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "locale": "es",
    "available_locales": ["es", "en"]
  }
}
```

---

#### Set Locale

**POST** `/api/i18n/locale`

Change user's locale preference.

**Request Body**:
```json
{
  "locale": "en"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Locale updated successfully"
}
```

---

#### Get Translations

**GET** `/api/i18n/{locale}`

Get all translations for a locale.

**Path Parameters**:
- `locale` (string, required): Locale code (es, en)

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "common": {
      "welcome": "Bienvenido",
      "logout": "Cerrar Sesión"
    },
    "users": {
      "title": "Usuarios",
      "create": "Crear Usuario"
    }
  }
}
```

---

#### Get Namespace Translations

**GET** `/api/i18n/{locale}/{namespace}`

Get translations for specific namespace.

**Path Parameters**:
- `locale` (string, required): Locale code
- `namespace` (string, required): Namespace (common, users, plugins, etc.)

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "title": "Usuarios",
    "create": "Crear Usuario",
    "edit": "Editar Usuario",
    "delete": "Eliminar Usuario"
  }
}
```

---

### System Status

**GET** `/api/status`

Get system status (useful for monitoring).

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "status": "operational",
    "version": "1.0.0",
    "database": "connected",
    "storage": "writable",
    "plugins": {
      "total": 15,
      "enabled": 12
    }
  }
}
```

---

## Webhooks

NexoSupport can send webhooks for important events.

### Webhook Configuration

Configure webhooks in Admin → Settings → Webhooks:

```json
{
  "url": "https://yourapp.com/webhook",
  "secret": "your-webhook-secret",
  "events": [
    "user.created",
    "user.updated",
    "user.deleted",
    "plugin.installed",
    "plugin.enabled",
    "plugin.disabled"
  ]
}
```

### Webhook Payload

**Headers**:
```
Content-Type: application/json
X-NexoSupport-Signature: sha256=...
X-NexoSupport-Event: user.created
```

**Body**:
```json
{
  "event": "user.created",
  "timestamp": "2025-11-12T14:30:52Z",
  "data": {
    "id": 123,
    "username": "john.doe",
    "email": "john@example.com",
    "created_at": "2025-11-12T14:30:52Z"
  }
}
```

### Verifying Webhooks

```php
$signature = $_SERVER['HTTP_X_NEXOSUPPORT_SIGNATURE'];
$payload = file_get_contents('php://input');
$expected = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

$event = json_decode($payload, true);
// Process event...
```

### Available Events

| Event | Triggered When |
|-------|---------------|
| `user.created` | New user created |
| `user.updated` | User updated |
| `user.deleted` | User deleted |
| `user.login` | User logged in |
| `user.logout` | User logged out |
| `plugin.installed` | Plugin installed |
| `plugin.enabled` | Plugin enabled |
| `plugin.disabled` | Plugin disabled |
| `plugin.uninstalled` | Plugin uninstalled |
| `plugin.updated` | Plugin updated |
| `role.created` | Role created |
| `role.updated` | Role updated |
| `role.deleted` | Role deleted |
| `permission.created` | Permission created |
| `permission.updated` | Permission updated |
| `permission.deleted` | Permission deleted |

---

## Rate Limiting

API endpoints are rate-limited to prevent abuse:

- **Anonymous requests**: 60 requests per hour
- **Authenticated requests**: 600 requests per hour
- **Admin requests**: 3000 requests per hour

**Rate Limit Headers**:
```
X-RateLimit-Limit: 600
X-RateLimit-Remaining: 547
X-RateLimit-Reset: 1699880400
```

**When rate limit exceeded (429)**:
```json
{
  "success": false,
  "message": "Rate limit exceeded",
  "retry_after": 3600
}
```

---

## Changelog

### Version 1.0.0 (2025-11-12)

- Initial API release
- Authentication endpoints
- User management
- Admin management
- Plugin system
- i18n support
- Webhook support

---

## Support

For API support or questions:

- **Documentation**: https://docs.nexosupport.com
- **GitHub Issues**: https://github.com/nexosupport/nexosupport/issues
- **Community Forum**: https://community.nexosupport.com
- **Email**: api-support@nexosupport.com

---

**Document Version**: 1.0.0
**Last Updated**: 2025-11-12
**License**: MIT
