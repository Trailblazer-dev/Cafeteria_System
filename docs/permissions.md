# Permissions System Documentation

## Overview

The Cafeteria Management System implements a comprehensive role-based access control (RBAC) system to manage user permissions. This system is handled by the `PermissionHandler` class, which provides methods for checking, assigning, and managing user permissions.

## Permission Tables

### Database Schema

The permission system relies on three primary tables:

1. **Role_Table**: Defines user roles
   - `Role_Id`: Unique identifier (e.g., R001, R002)
   - `Role`: Role name/description

2. **permissions**: Lists all available permissions
   - `permission_id`: Auto-incrementing ID
   - `permission_name`: Unique permission identifier string
   - `description`: Human-readable description

3. **role_permissions**: Maps roles to permissions
   - `id`: Auto-incrementing ID
   - `Role_Id`: Foreign key to Role_Table
   - `permission_id`: Foreign key to permissions

## Default Permissions

The system comes with the following pre-configured permissions:

| Permission Name      | Description                               |
|----------------------|-------------------------------------------|
| manage_cafeterias    | Can add, edit, and delete cafeterias      |
| manage_menu          | Can add, edit, and delete menu items      |
| manage_staff         | Can add, edit, and delete staff members   |
| manage_roles         | Can add, edit, and delete roles           |
| process_orders       | Can process and fulfill customer orders   |
| view_reports         | Can view sales and other reports          |
| generate_receipts    | Can generate and print receipts           |
| manage_inventory     | Can manage cafeteria inventory            |
| admin_dashboard      | Can access the admin dashboard            |

## Role-Based Access

### Default Roles

1. **Admin (R001)**
   - Has all permissions
   - Can manage the entire system

2. **Staff (R002)**
   - process_orders
   - generate_receipts

3. **Chef (R003)**
   - process_orders
   - generate_receipts
   - manage_menu
   - manage_inventory

## Using the PermissionHandler

### Initialization

```php
// Initialize with database connection and staff ID
$permHandler = new PermissionHandler($conn, $_SESSION['staff_id']);
```

### Checking Permissions

```php
// Check if user has a specific permission
if ($permHandler->hasPermission('manage_menu')) {
    // Show menu management interface
}
```

### Getting User Permissions

```php
// Get all permissions for a user
$permissions = $permHandler->getUserPermissions($staffId);
```

### Managing Role Permissions

```php
// Get all permissions for a specific role
$rolePermissions = $permHandler->getRolePermissions($roleId);

// Update permissions for a role
$permHandler->updateRolePermissions($roleId, $permissionIds);
```

## Permission Setup and Maintenance

### Creating Permission Tables

The system can automatically create required permission tables:

```php
// Create permission tables if they don't exist
PermissionHandler::ensureTablesExist($conn);
```

### Adding New Permissions

To add new permissions to the system:

1. Insert the permission into the `permissions` table:

```php
$stmt = $conn->prepare("INSERT INTO permissions (permission_name, description) VALUES (?, ?)");
$stmt->bind_param("ss", $permissionName, $permissionDescription);
$stmt->execute();
```

2. Assign the permission to roles as needed:

```php
$stmt = $conn->prepare("INSERT INTO role_permissions (Role_Id, permission_id) VALUES (?, ?)");
$stmt->bind_param("si", $roleId, $permissionId);
$stmt->execute();
```

## Security Considerations

- The Admin role (R001) automatically has all permissions
- Failed permission checks default to "deny" for security
- The system handles missing tables gracefully during setup
- Permission queries are prepared statements to prevent SQL injection
