<?php
class PermissionHandler {
    private $conn;
    private $staffId;
    private $roleId;
    private $permissions = null;
    
    /**
     * Constructor - initialize with database connection and staff ID
     */
    public function __construct($conn, $staffId = null, $roleId = null) {
        $this->conn = $conn;
        $this->staffId = $staffId;
        $this->roleId = $roleId;
        
        // If no role ID provided but we have staff ID, get the role
        if ($staffId !== null && $roleId === null) {
            $this->loadUserRole();
        }
    }
    
    /**
     * Load the user's role from the staff table
     */
    private function loadUserRole() {
        try {
            $stmt = $this->conn->prepare("SELECT Role_Id FROM staff WHERE staffId = ?");
            if ($stmt) {
                $stmt->bind_param("i", $this->staffId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $this->roleId = $row['Role_Id'];
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error loading user role: " . $e->getMessage());
        }
    }
    
    /**
     * Check if the user has a specific permission
     */
    public function hasPermission($permissionName) {
        try {
            // Admin role always has all permissions
            if ($this->roleId === 'R001') {
                return true;
            }
            
            // If no role, no permissions
            if (!$this->roleId) {
                return false;
            }
            
            // For simplicity, as a fallback, always allow basic permissions for any role
            $basic_permissions = ['process_orders', 'generate_receipts'];
            if (in_array($permissionName, $basic_permissions)) {
                return true;
            }
            
            // Try to load from database if possible
            // This will handle more complex permissions
            $permissions = $this->getUserPermissions($this->staffId);
            return in_array($permissionName, $permissions);
        } catch (Exception $e) {
            error_log("Permission check error: " . $e->getMessage());
            // Return false for safety
            return false;
        }
    }
    
    /**
     * Load all permissions for the current role
     */
    private function loadPermissions() {
        $this->permissions = [];
        
        if (!$this->roleId) {
            return;
        }
        
        try {
            // Check if tables exist first
            $permissionTableExists = $this->conn->query("SHOW TABLES LIKE 'permissions'")->num_rows > 0;
            $rolePermissionsTableExists = $this->conn->query("SHOW TABLES LIKE 'role_permissions'")->num_rows > 0;
            
            if (!$permissionTableExists || !$rolePermissionsTableExists) {
                return; // Can't load permissions if tables don't exist
            }
            
            $stmt = $this->conn->prepare(
                "SELECT p.permission_name 
                 FROM permissions p
                 JOIN role_permissions rp ON p.permission_id = rp.permission_id
                 WHERE rp.Role_Id = ?"
            );
            
            if ($stmt) {
                $stmt->bind_param("s", $this->roleId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $this->permissions[] = $row['permission_name'];
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error loading permissions: " . $e->getMessage());
            // Leave permissions array empty on error
        }
    }
    
    /**
     * Check if tables exist and create if necessary
     */
    public static function ensureTablesExist($conn) {
        try {
            // Check if permissions table exists
            $result = $conn->query("SHOW TABLES LIKE 'permissions'");
            if ($result->num_rows == 0) {
                // Table doesn't exist, create the permissions table first
                $sqlPermissions = "CREATE TABLE IF NOT EXISTS permissions (
                    permission_id INT AUTO_INCREMENT PRIMARY KEY,
                    permission_name VARCHAR(50) NOT NULL UNIQUE,
                    description VARCHAR(255) NOT NULL
                )";
                
                if (!$conn->query($sqlPermissions)) {
                    throw new Exception("Failed to create permissions table: " . $conn->error);
                }
                
                // Insert base permissions
                $defaultPermissions = [
                    ['manage_cafeterias', 'Can add, edit, and delete cafeterias'],
                    ['manage_menu', 'Can add, edit, and delete menu items'],
                    ['manage_staff', 'Can add, edit, and delete staff members'],
                    ['manage_roles', 'Can add, edit, and delete roles'],
                    ['process_orders', 'Can process and fulfill customer orders'],
                    ['view_reports', 'Can view sales and other reports'],
                    ['generate_receipts', 'Can generate and print receipts'],
                    ['manage_inventory', 'Can manage cafeteria inventory'],
                    ['admin_dashboard', 'Can access the admin dashboard']
                ];
                
                foreach ($defaultPermissions as $perm) {
                    $stmt = $conn->prepare("INSERT INTO permissions (permission_name, description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $perm[0], $perm[1]);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert permission {$perm[0]}: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
            
            // Check Role_Table structure to ensure compatibility
            $roleTableResult = $conn->query("DESCRIBE Role_Table");
            $roleIdType = "VARCHAR(10)"; // Default type
            
            if ($roleTableResult) {
                while ($row = $roleTableResult->fetch_assoc()) {
                    if ($row['Field'] === 'Role_Id') {
                        $roleIdType = $row['Type'];
                        break;
                    }
                }
            }
            
            // Check if role_permissions table exists
            $result = $conn->query("SHOW TABLES LIKE 'role_permissions'");
            if ($result->num_rows == 0) {
                // Table doesn't exist, create the role_permissions table
                // Create without foreign key constraints for safety
                $sqlRolePermissions = "CREATE TABLE IF NOT EXISTS role_permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    Role_Id $roleIdType NOT NULL,
                    permission_id INT NOT NULL,
                    UNIQUE KEY unique_role_permission (Role_Id, permission_id)
                )";
                
                if (!$conn->query($sqlRolePermissions)) {
                    throw new Exception("Failed to create role_permissions table: " . $conn->error);
                }
                
                // Grant all permissions to admin role (R001)
                $result = $conn->query("SELECT permission_id FROM permissions");
                while ($row = $result->fetch_assoc()) {
                    $permission_id = $row['permission_id'];
                    $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (Role_Id, permission_id) VALUES ('R001', ?)");
                    $stmt->bind_param("i", $permission_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to assign permission #{$permission_id} to admin: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating permission tables: " . $e->getMessage());
            throw $e; // Re-throw to handle in the calling code
        }
    }
    
    /**
     * Get all permissions with their IDs
     */
    public function getAllPermissions() {
        $permissions = [];
        
        try {
            // Check if permissions table exists first
            $result = $this->conn->query("SHOW TABLES LIKE 'permissions'");
            if ($result->num_rows == 0) {
                // Try to create tables
                self::ensureTablesExist($this->conn);
            }
            
            $query = "SELECT * FROM permissions ORDER BY permission_name";
            $result = $this->conn->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $permissions[] = $row;
                }
            }
        } catch (Exception $e) {
            error_log("Error getting permissions: " . $e->getMessage());
            // Return empty array on error
        }
        
        return $permissions;
    }
    
    /**
     * Get all permissions for a specific role
     */
    public function getRolePermissions($roleId) {
        $permissions = [];
        
        try {
            // Check if tables exist first
            $permissionTableExists = $this->conn->query("SHOW TABLES LIKE 'permissions'")->num_rows > 0;
            $rolePermissionsTableExists = $this->conn->query("SHOW TABLES LIKE 'role_permissions'")->num_rows > 0;
            
            if (!$permissionTableExists || !$rolePermissionsTableExists) {
                // Try to create tables
                self::ensureTablesExist($this->conn);
            }
            
            $stmt = $this->conn->prepare(
                "SELECT p.permission_id, p.permission_name, p.description 
                 FROM permissions p
                 JOIN role_permissions rp ON p.permission_id = rp.permission_id
                 WHERE rp.Role_Id = ?
                 ORDER BY p.permission_name"
            );
            
            if ($stmt) {
                $stmt->bind_param("s", $roleId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $permissions[] = $row;
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error getting role permissions: " . $e->getMessage());
            // Return empty array on error
        }
        
        return $permissions;
    }
    
    /**
     * Update permissions for a role
     */
    public function updateRolePermissions($roleId, $permissionIds) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Make sure role_permissions table exists
            $result = $this->conn->query("SHOW TABLES LIKE 'role_permissions'");
            if ($result->num_rows == 0) {
                // Ensure tables exist before proceeding
                self::ensureTablesExist($this->conn);
            }
            
            // Delete existing permissions
            $stmt = $this->conn->prepare("DELETE FROM role_permissions WHERE Role_Id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare delete statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("s", $roleId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete permissions: " . $stmt->error);
            }
            $stmt->close();
            
            // If there are new permissions to add
            if (!empty($permissionIds)) {
                $stmt = $this->conn->prepare("INSERT INTO role_permissions (Role_Id, permission_id) VALUES (?, ?)");
                if (!$stmt) {
                    throw new Exception("Failed to prepare insert statement: " . $this->conn->error);
                }
                
                foreach ($permissionIds as $permId) {
                    $stmt->bind_param("si", $roleId, $permId);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert permission: " . $stmt->error);
                    }
                }
                $stmt->close();
            }
            
            // Commit changes
            $this->conn->commit();
            return true;
        } 
        catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            error_log("Error updating permissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all permissions for a user
     * 
     * @param int $staffId The staff ID
     * @return array Array of permission names
     */
    public function getUserPermissions($staffId) {
        $permissions = [];
        
        // Add basic permissions for every staff member
        $permissions[] = 'process_orders';
        $permissions[] = 'generate_receipts';
        
        // Attempt to get from database, but don't fail if tables don't exist
        try {
            // Get role ID for this staff member
            $stmt = $this->conn->prepare("SELECT Role_Id FROM staff WHERE staffId = ?");
            $stmt->bind_param("i", $staffId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $role_id = $row['Role_Id'];
                
                // Basic role-based permissions
                if ($role_id === 'R001') { // Admin
                    $permissions = array_merge($permissions, [
                        'admin_dashboard', 'manage_cafeterias', 'manage_menu', 
                        'manage_roles', 'view_reports', 'manage_inventory'
                    ]);
                } else if ($role_id === 'R002') { // Staff
                    // Already has basic permissions
                } else if ($role_id === 'R003') { // Chef
                    $permissions[] = 'manage_menu';
                    $permissions[] = 'manage_inventory';
                }
                
                // Try to get from permission tables if they exist
                $table_exists = $this->conn->query("SHOW TABLES LIKE 'permissions'");
                if ($table_exists && $table_exists->num_rows > 0) {
                    $join_exists = $this->conn->query("SHOW TABLES LIKE 'role_permissions'");
                    if ($join_exists && $join_exists->num_rows > 0) {
                        $stmt = $this->conn->prepare("
                            SELECT p.permission_name 
                            FROM permissions p
                            JOIN role_permissions rp ON p.permission_id = rp.permission_id
                            WHERE rp.Role_Id = ?
                        ");
                        $stmt->bind_param("s", $role_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()) {
                            if (!in_array($row['permission_name'], $permissions)) {
                                $permissions[] = $row['permission_name'];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error in getUserPermissions: " . $e->getMessage());
            // Continue with the basic permissions we already set
        }
        
        return $permissions;
    }
}
?>
