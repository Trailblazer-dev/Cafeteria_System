# Cafeteria Management System Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Installation Guide](#installation-guide)
3. [System Architecture](#system-architecture)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Key Features](#key-features)
6. [Database Schema](#database-schema)
7. [Deployment Guide](#deployment-guide)
8. [Troubleshooting](#troubleshooting)

## Project Overview

The Cafeteria Management System is a comprehensive web application designed to streamline cafeteria operations within educational institutions. It facilitates order processing, inventory management, menu administration, and student self-service ordering.

### Key Capabilities

- Student self-service ordering system
- Order processing and fulfillment
- Menu management
- Payment processing
- Receipt generation
- Role-based access control
- Cafeteria administration

## Installation Guide

### System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP/LAMPP for local development

### Setup Instructions

1. **Clone the repository**
   ```
   git clone [repository-url]
   ```

2. **Database Setup**
   - Create a new MySQL database named `team_cafeteria`
   - Import the database schema from `database/schema.sql`
   - Sample data can be imported from `database/sample_data.sql`

3. **Configuration**
   - Update the database connection details in `includes/db.php`
   - Configure environment settings in `includes/config.php`

4. **Web Server Configuration**
   - Point your web server document root to the project directory
   - Ensure PHP has appropriate write permissions for logs and uploads

5. **Initial Login**
   - Access the system at `http://localhost/cafeteria/`
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123`

## System Architecture

The Cafeteria Management System follows a traditional PHP MVC-like architecture:

### Components

- **Front-end**: HTML5, CSS (TailwindCSS), JavaScript
- **Back-end**: PHP
- **Database**: MySQL
- **Authentication**: Custom PHP session-based authentication

### Directory Structure

```
/cafeteria/
├── assets/              # Static assets (JS, CSS, images)
├── database/            # Database scripts
├── docs/                # Documentation
├── includes/
│   ├── components/      # Reusable UI components
│   ├── services/        # Business logic services
│   └── PermissionHandler.php # Authorization system
├── *.php                # Main application pages
```

## User Roles & Permissions

The system implements a role-based access control system with the following primary roles:

### Default Roles

1. **Admin (R001)**
   - Full system access
   - Manage cafeterias, staff, roles, permissions

2. **Staff (R002)**
   - Process orders
   - Generate receipts

3. **Chef (R003)**
   - Manage menu items
   - Manage inventory
   - Process orders

### Permission System

The `PermissionHandler` class manages role-based permissions:

- Permissions are stored in the `permissions` table
- Role-permission assignments are managed in `role_permissions`
- Each permission grants access to specific system functionality

## Key Features

### Order Management

- Students can browse menu items and place orders
- Staff can view, process, and fulfill orders
- Order tracking from placement to fulfillment

### Menu Management

- Add, edit, and delete menu items
- Set item availability
- Associate items with specific cafeterias

### Payment Processing

- Multiple payment methods supported
- Secure payment tracking
- Order-payment relationship management

### Receipt Generation

- Generate digital receipts
- Print-friendly receipt format
- Order history tracking

### Cafeteria Administration

- Manage multiple cafeterias
- Assign staff to cafeterias
- Configure cafeteria-specific menus

## Database Schema

### Core Tables

- `student_table`: Stores student information
- `staff`: Stores staff member details
- `Role_Table`: Defines system roles
- `permissions`: Available system permissions
- `role_permissions`: Links roles to permissions
- `Cafeteria`: Cafeteria locations
- `Item_table`: Menu items
- `orders`: Customer orders
- `order_details`: Line items for each order
- `payment`: Payment records
- `paymentMethod`: Available payment methods

### Relationships Diagram

See `docs/database_diagram.md` for a complete database relationship diagram.

## Deployment Guide

For detailed instructions on deploying the system and making it accessible via URL, see [Deployment Guide](deployment_guide.md).

This guide covers:
- Local deployment with XAMPP/LAMPP
- Shared hosting deployment
- VPS/dedicated server setup
- Domain configuration
- SSL setup
- Advanced deployment options

## Troubleshooting

### Common Issues

1. **Permission Errors**
   - Check that MySQL user has appropriate permissions
   - Verify file permissions on the web server

2. **Login Problems**
   - Clear browser cookies and cache
   - Check user credentials in the database

3. **Menu Items Not Displaying**
   - Verify items are marked as available
   - Check cafeteria assignment for menu items

4. **Orders Not Processing**
   - Check staff permissions for order processing
   - Verify database connectivity

### Logging

System logs can be found in:
- Application errors: PHP error log
- Custom logs: `logs/application.log` 

### Support

For additional support, please contact the system administrator or refer to the developer documentation.
