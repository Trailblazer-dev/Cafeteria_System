# Developer Guide

## Architecture Overview

The Cafeteria Management System follows a traditional PHP application structure with separation of concerns:

- **UI Layer**: PHP templates with TailwindCSS for styling
- **Business Logic**: PHP classes in the `includes/services` directory
- **Data Access**: Direct database queries with prepared statements
- **Authentication/Authorization**: Session-based with role-based permissions

## Development Environment Setup

### Requirements

- PHP 7.4+ with extensions:
  - mysqli
  - session
  - json
- MySQL 5.7+
- Apache 2.4+ with mod_rewrite
- XAMPP/LAMPP (recommended for local development)

### Setup Steps

1. **Clone the repository**
   ```
   git clone [repository-url]
   ```

2. **Configure local environment**
   - Set up a virtual host (optional but recommended)
   - Ensure the web server has write permissions to necessary directories

3. **Database setup**
   - Create database and import schema

4. **Configuration**
   - Set `ENVIRONMENT` to `development` in `includes/config.php`

## Code Structure

### Key Directories and Files

- **`/includes`**: Core application libraries
  - **`/components`**: Reusable UI components
  - **`/services`**: Business logic services
  - **`db.php`**: Database connection
  - **`config.php`**: Application configuration
  - **`functions.php`**: Utility functions
  - **`PermissionHandler.php`**: Authorization system

- **`/assets`**: Static resources
  - **`/js`**: JavaScript files
  - **`/css`**: CSS files (if not using CDN for TailwindCSS)
  - **`/images`**: Image assets

- **Application Pages**: PHP files in root directory
  - **`dashboard.php`**: Main admin dashboard
  - **`student_order.php`**: Student ordering interface
  - **`process_orders.php`**: Order processing for staff
  - And many others...

## Key Components

### Permission Handler

The `PermissionHandler` class manages role-based access control:

```php
// Initialize
$permHandler = new PermissionHandler($conn, $staffId);

// Check permission
if ($permHandler->hasPermission('manage_menu')) {
    // User can manage menu items
}
```

### Services

Service classes encapsulate business logic:

- `StatsService`: Dashboard statistics
- `OrderService`: Order processing logic

### Database Utilities

- Connection management in `db.php`
- `sanitize()` function for input sanitization
- Transaction handling for multi-step operations

## Adding New Features

### Adding Pages

1. Create a new PHP file in the root directory
2. Include necessary files:
   ```php
   include 'includes/config.php';
   include 'includes/db.php';
   include 'includes/PermissionHandler.php';
   ```
3. Add permission checks
4. Include header and navigation components
5. Implement page functionality
6. Include footer component

### Adding Permissions

1. Add new permission to the `permissions` table:
   ```php
   $stmt = $conn->prepare("INSERT INTO permissions (permission_name, description) VALUES (?, ?)");
   $stmt->bind_param("ss", $name, $description);
   $stmt->execute();
   ```

2. Add the permission to relevant roles

### Adding New API Endpoints

For AJAX functionality:

1. Create a new PHP file to handle the request
2. Validate input and check permissions
3. Process the request and return JSON response:
   ```php
   header('Content-Type: application/json');
   echo json_encode($result);
   ```

## Testing

### Manual Testing

- Test each user role and permission combination
- Verify CRUD operations on all entities
- Test order workflow from creation to completion

### Automated Testing

- PHP Unit tests can be added in a `/tests` directory
- API endpoints can be tested with Postman collections

## Deployment

### Production Deployment

1. Set `ENVIRONMENT` to `production` in `includes/config.php`
2. Optimize the database (add indices, run ANALYZE TABLE)
3. Ensure secure file permissions
4. Configure error logging appropriately

### Security Considerations

- All user inputs are sanitized using the `sanitize()` function
- Prepared statements prevent SQL injection
- Password hashing for authentication
- Session management for authorization
- XSS prevention with output escaping

## Extending the System

### Adding New Modules

1. Plan the database schema changes
2. Create necessary tables
3. Add service classes in `includes/services/`
4. Create UI components and pages
5. Update permissions as needed

### Customizing UI

- The system uses TailwindCSS for styling
- Modify component templates in `includes/components/`
- JavaScript enhancements can be added to `assets/js/`

## Code Standards

- PSR-1/PSR-2 coding style recommended
- Comment complex logic sections
- Use prepared statements for all database queries
- Sanitize all user inputs
- Validate form data on both client and server sides

## Common Code Patterns

### Form Processing Pattern

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $input = sanitize($_POST['input']);
    
    // Validate data
    if (empty($input)) {
        $_SESSION['error'] = "Input required";
        header("Location: same_page.php");
        exit;
    }
    
    // Process data with try-catch for error handling
    try {
        // Database operations
        $_SESSION['message'] = "Success message";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error message: " . $e->getMessage();
    }
    
    // Redirect
    header("Location: next_page.php");
    exit;
}
```

### Permission Check Pattern

```php
// Check login
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

// Check permission
$permHandler = new PermissionHandler($conn, $_SESSION['staff_id']);
if (!$permHandler->hasPermission('required_permission')) {
    header("Location: unauthorized.php");
    exit;
}
```

## Troubleshooting Development Issues

- Enable error display in development:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```

- Verify database connection parameters
- Check file permissions for writing logs/uploads
- Validate table structures against expected schema
