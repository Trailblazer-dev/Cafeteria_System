# Deployment Guide

This guide explains how to deploy the Cafeteria Management System so it can be accessed via URL in various environments.

## Table of Contents
1. [Local Deployment](#local-deployment)
2. [Shared Hosting Deployment](#shared-hosting-deployment)
3. [VPS/Dedicated Server Deployment](#vpsdedicated-server-deployment)
4. [Domain Configuration](#domain-configuration)
5. [SSL Configuration](#ssl-configuration)
6. [Advanced Deployment Options](#advanced-deployment-options)

## Local Deployment

### Using XAMPP/LAMPP

The system is already set up in the XAMPP/LAMPP environment at `/opt/lampp/htdocs/cafeteria/`. To access it locally:

1. **Start XAMPP/LAMPP services**
   ```
   sudo /opt/lampp/lampp start
   ```
   This starts Apache and MySQL services.

2. **Access via localhost URL**
   Open your browser and navigate to:
   ```
   http://localhost/cafeteria/
   ```

3. **Set up virtual host (optional but recommended)**

   a. Edit your Apache configuration:
   ```
   sudo nano /opt/lampp/etc/httpd.conf
   ```
   
   b. Ensure this line is uncommented:
   ```
   Include etc/extra/httpd-vhosts.conf
   ```
   
   c. Edit the virtual hosts file:
   ```
   sudo nano /opt/lampp/etc/extra/httpd-vhosts.conf
   ```
   
   d. Add a virtual host configuration:
   ```apache
   <VirtualHost *:80>
       ServerAdmin webmaster@cafeteria.local
       DocumentRoot "/opt/lampp/htdocs/cafeteria"
       ServerName cafeteria.local
       ServerAlias www.cafeteria.local
       ErrorLog "logs/cafeteria-error_log"
       CustomLog "logs/cafeteria-access_log" common
       <Directory "/opt/lampp/htdocs/cafeteria">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   
   e. Edit your hosts file:
   ```
   sudo nano /etc/hosts
   ```
   
   Add this line:
   ```
   127.0.0.1 cafeteria.local www.cafeteria.local
   ```
   
   f. Restart Apache:
   ```
   sudo /opt/lampp/lampp restart
   ```
   
   g. Access via your custom domain:
   ```
   http://cafeteria.local/
   ```

### Using Docker (Alternative)

For a containerized approach:

1. Create a `Dockerfile` in the project root:

```Dockerfile
FROM php:7.4-apache
RUN docker-php-ext-install mysqli
COPY . /var/www/html/
```

2. Create a `docker-compose.yml` file:

```yaml
version: '3'
services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html/
    depends_on:
      - db
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: team_cafeteria
      MYSQL_USER: cafeteria
      MYSQL_PASSWORD: cafeteriapassword
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  db_data:
```

3. Run with Docker Compose:
```
docker-compose up -d
```

4. Access at:
```
http://localhost:8080/
```

## Shared Hosting Deployment

### Preparation

1. **Export your database**
   ```
   mysqldump -u root -p team_cafeteria > team_cafeteria_backup.sql
   ```

2. **Create a ZIP archive of your project**
   ```
   cd /opt/lampp/htdocs/
   zip -r cafeteria.zip cafeteria
   ```

### Deployment Steps

1. **Sign up with a web hosting provider** that supports PHP 7.4+ and MySQL 5.7+

2. **Access your hosting control panel** (cPanel, Plesk, etc.)

3. **Create a database**
   - Go to MySQL Databases or Database section
   - Create a new database (e.g., `username_cafeteria`)
   - Create or assign a database user with full privileges

4. **Import the database**
   - Use phpMyAdmin or similar database tool
   - Import `team_cafeteria_backup.sql`

5. **Upload application files**
   - Use FTP client (FileZilla, etc.) or web-based file manager
   - Connect to your hosting using provided credentials
   - Upload all files to the public directory (usually `public_html`, `www`, or `htdocs`)
   - You can upload directly or extract the zip archive

6. **Update database connection settings**
   - Edit `includes/db.php`:
   ```php
   $host = "localhost"; // Usually localhost, but check with your provider
   $user = "username_cafeteria"; // Your database username
   $password = "your_password"; // Your database password
   $database = "username_cafeteria"; // Your database name
   ```

7. **Set file permissions**
   - Set directories to 755 (drwxr-xr-x)
   - Set files to 644 (rw-r--r--)
   - Set write permissions for uploads and logs directories (if applicable)
   ```
   chmod -R 755 /path/to/directories
   chmod -R 644 /path/to/files
   ```

8. **Access your website**
   ```
   http://yourdomain.com/
   ```
   or if installed in a subdirectory:
   ```
   http://yourdomain.com/cafeteria/
   ```

## VPS/Dedicated Server Deployment

### Server Setup

1. **Provision a VPS or Dedicated Server** with your preferred provider (AWS, DigitalOcean, Linode, etc.)

2. **Connect via SSH**
   ```
   ssh username@server_ip
   ```

3. **Update the system**
   ```
   sudo apt update && sudo apt upgrade -y
   ```

4. **Install required packages**
   ```
   sudo apt install apache2 mysql-server php php-mysql php-mbstring php-xml php-json zip unzip -y
   ```

5. **Configure MySQL**
   ```
   sudo mysql_secure_installation
   ```
   
   Create database and user:
   ```
   sudo mysql -u root -p
   ```
   ```sql
   CREATE DATABASE team_cafeteria;
   CREATE USER 'cafeteria_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON team_cafeteria.* TO 'cafeteria_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

6. **Configure Apache**
   ```
   sudo nano /etc/apache2/sites-available/cafeteria.conf
   ```
   
   Add configuration:
   ```apache
   <VirtualHost *:80>
       ServerAdmin webmaster@yourdomain.com
       ServerName yourdomain.com
       ServerAlias www.yourdomain.com
       DocumentRoot /var/www/cafeteria
       
       <Directory /var/www/cafeteria>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/cafeteria_error.log
       CustomLog ${APACHE_LOG_DIR}/cafeteria_access.log combined
   </VirtualHost>
   ```

7. **Enable site and modules**
   ```
   sudo a2ensite cafeteria.conf
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

8. **Deploy application files**
   ```
   sudo mkdir -p /var/www/cafeteria
   sudo chown -R $USER:$USER /var/www/cafeteria
   ```
   
   Transfer files using SCP:
   ```
   scp -r /opt/lampp/htdocs/cafeteria/* username@server_ip:/var/www/cafeteria/
   ```
   
   Or clone from Git if available:
   ```
   cd /var/www/cafeteria
   git clone [repository-url] .
   ```

9. **Import database**
   ```
   mysql -u cafeteria_user -p team_cafeteria < team_cafeteria_backup.sql
   ```

10. **Update database connection settings**
    ```
    nano /var/www/cafeteria/includes/db.php
    ```
    Update with your VPS credentials

11. **Set proper permissions**
    ```
    sudo chown -R www-data:www-data /var/www/cafeteria
    sudo find /var/www/cafeteria -type d -exec chmod 755 {} \;
    sudo find /var/www/cafeteria -type f -exec chmod 644 {} \;
    ```

## Domain Configuration

1. **Register a domain** with a domain registrar (Namecheap, GoDaddy, etc.)

2. **Update DNS settings** to point to your server's IP address:
   - A record: `@` pointing to your server IP
   - A record: `www` pointing to your server IP

3. **Wait for DNS propagation** (can take 24-48 hours)

## SSL Configuration

### Using Let's Encrypt (free SSL)

1. **Install Certbot**
   ```
   sudo apt install certbot python3-certbot-apache -y
   ```

2. **Obtain SSL certificate**
   ```
   sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
   ```

3. **Auto-renewal setup** (Certbot does this automatically)
   To test renewal:
   ```
   sudo certbot renew --dry-run
   ```

### Using Paid Certificate

1. **Purchase SSL certificate** from a provider (DigiCert, Comodo, etc.)

2. **Generate CSR (Certificate Signing Request)**
   ```
   sudo openssl req -new -newkey rsa:2048 -nodes -keyout yourdomain.key -out yourdomain.csr
   ```

3. **Submit CSR to certificate provider** and download certificate files

4. **Install certificate**
   ```
   sudo nano /etc/apache2/sites-available/cafeteria-ssl.conf
   ```
   Configure with paths to certificate files

5. **Enable SSL module and site**
   ```
   sudo a2enmod ssl
   sudo a2ensite cafeteria-ssl.conf
   sudo systemctl restart apache2
   ```

## Advanced Deployment Options

### Continuous Integration/Deployment

1. **Set up a CI/CD pipeline** using GitHub Actions, GitLab CI, or Jenkins

2. **Create a deployment workflow**:
   - Run tests
   - Build assets
   - Deploy to server via SSH
   - Run database migrations

### Load Balancing

For high-traffic implementations:

1. **Set up multiple server instances**
2. **Configure load balancer** (NGINX, HAProxy, or cloud provider solutions)
3. **Implement session sharing** using Redis or Memcached

### Monitoring

1. **Set up server monitoring** with tools like:
   - New Relic
   - Prometheus + Grafana
   - Datadog

2. **Configure alerts** for downtime, high server load, etc.

## Troubleshooting Common Deployment Issues

- **500 Internal Server Error**: Check Apache error logs and PHP error logs
- **Database Connection Failures**: Verify credentials and remote access permissions
- **Permission Issues**: Check file and directory permissions
- **URL Rewrites Not Working**: Ensure mod_rewrite is enabled and .htaccess is properly configured

## Maintenance

- **Regular Backups**: Schedule regular database and file backups
- **Updates**: Keep PHP, MySQL, and Apache updated
- **Security Patches**: Monitor and apply security updates promptly
