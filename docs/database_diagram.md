# Database Schema

## Overview

The Cafeteria Management System uses a relational database with the following primary tables and relationships.

## Core Tables

### Student Management
- **student_table**
  - `reg_no`: Primary key, student registration number
  - `fistname`: Student's first name
  - `lastname`: Student's last name
  - `phone`: Contact phone number

### Staff Management
- **staff**
  - `staffId`: Primary key
  - `firstName`: Staff first name
  - `lastName`: Staff last name
  - `username`: Login username
  - `password`: Hashed password
  - `Role_Id`: Foreign key to Role_Table
  - `Cafeteria_Id`: Foreign key to Cafeteria

### Role & Permission System
- **Role_Table**
  - `Role_Id`: Primary key (e.g., R001, R002)
  - `Role`: Role name

- **permissions**
  - `permission_id`: Primary key
  - `permission_name`: Unique permission identifier
  - `description`: Human-readable description

- **role_permissions**
  - `id`: Primary key
  - `Role_Id`: Foreign key to Role_Table
  - `permission_id`: Foreign key to permissions table

### Cafeteria & Menu Management
- **Cafeteria**
  - `Cafeteria_Id`: Primary key
  - `Name`: Cafeteria name
  - `Location`: Physical location

- **Item_table**
  - `Item_Id`: Primary key
  - `Name`: Item name
  - `Price`: Item price
  - `Availability`: Boolean availability status
  - `Cafeteria_Id`: Foreign key to Cafeteria

### Order Management
- **orders**
  - `order_id`: Primary key
  - `reg_no`: Foreign key to student_table
  - `order_date`: Timestamp
  - `total_cost`: Order total

- **order_details**
  - `details_id`: Primary key
  - `order_id`: Foreign key to orders
  - `Item_Id`: Foreign key to Item_table
  - `quantity`: Quantity ordered

### Payment System
- **payment**
  - `payment_id`: Primary key (e.g., P001)
  - `order_id`: Foreign key to orders
  - `amount`: Payment amount
  - `date`: Payment date
  - `methodId`: Foreign key to paymentMethod

- **paymentMethod**
  - `methodId`: Primary key
  - `method`: Payment method name

## Relationships Diagram

```
student_table 1---* orders
orders 1---* order_details
Item_table 1---* order_details
orders 1---1 payment
paymentMethod 1---* payment
Cafeteria 1---* Item_table
Cafeteria 1---* staff
Role_Table 1---* staff
Role_Table 1---* role_permissions
permissions 1---* role_permissions
```

## Key Constraints

- Foreign keys maintain referential integrity between related tables
- Unique constraints prevent duplicate entries for critical fields
- NOT NULL constraints ensure required data is provided

## Indexing Strategy

The following columns are indexed for performance:
- All primary keys
- Foreign keys for relationship lookups
- Frequently queried columns like `order_date` and `Cafeteria_Id`

## Sample Queries

### Get Orders with Student and Item Details
```sql
SELECT o.order_id, s.fistname, s.lastname, 
       i.Name, od.quantity, i.Price, 
       (i.Price * od.quantity) as item_total
FROM orders o
JOIN student_table s ON o.reg_no = s.reg_no
JOIN order_details od ON o.order_id = od.order_id
JOIN Item_table i ON od.Item_Id = i.Item_Id
ORDER BY o.order_date DESC;
```

### Get Staff with Their Roles and Cafeteria
```sql
SELECT s.staffId, s.firstName, s.lastName, 
       r.Role, c.Name as cafeteria
FROM staff s
JOIN Role_Table r ON s.Role_Id = r.Role_Id
LEFT JOIN Cafeteria c ON s.Cafeteria_Id = c.Cafeteria_Id;
```

### Get User Permissions
```sql
SELECT p.permission_name
FROM permissions p
JOIN role_permissions rp ON p.permission_id = rp.permission_id
JOIN staff s ON rp.Role_Id = s.Role_Id
WHERE s.staffId = ?;
```
