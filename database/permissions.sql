-- Create a dedicated permissions table if it doesn't exist
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL
);

-- Create role_permissions table if it doesn't exist
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Role_Id VARCHAR(10) NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (Role_Id) REFERENCES Role_Table(Role_Id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (Role_Id, permission_id)
);

-- Insert base permissions
INSERT INTO permissions (permission_name, description) VALUES
('manage_cafeterias', 'Can add, edit, and delete cafeterias'),
('manage_menu', 'Can add, edit, and delete menu items'),
('manage_staff', 'Can add, edit, and delete staff members'),
('manage_roles', 'Can add, edit, and delete roles'),
('process_orders', 'Can process and fulfill customer orders'),
('view_reports', 'Can view sales and other reports'),
('generate_receipts', 'Can generate and print receipts'),
('manage_inventory', 'Can manage cafeteria inventory'),
('admin_dashboard', 'Can access the admin dashboard')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant all permissions to admin role (R001)
INSERT IGNORE INTO role_permissions (Role_Id, permission_id)
SELECT 'R001', permission_id FROM permissions;
