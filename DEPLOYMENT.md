# CineVerse Deployment Guide

This guide provides detailed instructions for deploying CineVerse to various hosting environments.

## 🚀 Production Deployment

### Pre-deployment Checklist

- [ ] **Environment Configuration**
  - Set `APP_ENV=production`
  - Set `APP_DEBUG=false`
  - Generate secure `JWT_SECRET`
  - Configure database credentials
  - Set up email service (SMTP)
  - Configure payment gateway credentials

- [ ] **Security Setup**
  - SSL certificate installed
  - Firewall configured
  - Database access restricted
  - File permissions set correctly
  - Backup strategy implemented

- [ ] **Performance Optimization**
  - Caching configured (Redis/Memcached)
  - Database optimized
  - CDN configured for assets
  - Gzip compression enabled

## 🌐 Cloud Hosting Deployment

### AWS Deployment

#### 1. EC2 Instance Setup
```bash
# Launch Ubuntu 22.04 LTS instance
# Connect via SSH
ssh -i your-key.pem ubuntu@your-ec2-ip

# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd composer git

# Configure MySQL
sudo mysql_secure_installation
```

#### 2. Application Deployment
```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/your-username/cineverse.git
cd cineverse

# Install dependencies
sudo composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/cineverse
sudo chmod -R 755 /var/www/cineverse
sudo chmod -R 775 storage public/uploads

# Configure environment
sudo cp .env.example .env
sudo nano .env
```

#### 3. Database Setup
```bash
# Create database
sudo mysql -u root -p
CREATE DATABASE cineverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cineverse_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON cineverse_db.* TO 'cineverse_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u cineverse_user -p cineverse_db < database/schema.sql
```

#### 4. Nginx Configuration
```nginx
# /etc/nginx/sites-available/cineverse
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/cineverse/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\. {
        deny all;
    }
}
```

#### 5. SSL Setup with Let's Encrypt
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### DigitalOcean Deployment

#### 1. Droplet Setup
```bash
# Create Ubuntu 22.04 droplet
# Connect via SSH
ssh root@your-droplet-ip

# Create non-root user
adduser cineverse
usermod -aG sudo cineverse
su - cineverse

# Install LAMP stack
sudo apt update
sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd composer git
```

#### 2. Apache Configuration
```apache
# /etc/apache2/sites-available/cineverse.conf
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/cineverse/public
    
    <Directory /var/www/cineverse/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/cineverse_error.log
    CustomLog ${APACHE_LOG_DIR}/cineverse_access.log combined
</VirtualHost>
```

```bash
# Enable site and modules
sudo a2ensite cineverse.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 🏠 Shared Hosting Deployment

### cPanel/Shared Hosting

#### 1. File Upload
```bash
# Create zip file locally
zip -r cineverse.zip . -x "*.git*" "node_modules/*" "vendor/*"

# Upload via cPanel File Manager or FTP
# Extract to public_html or subdirectory
```

#### 2. Database Setup
- Create MySQL database via cPanel
- Import `database/schema.sql`
- Update `.env` with database credentials

#### 3. Configuration
```php
// public/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

## 🐳 Docker Deployment

### Docker Compose Setup
```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    environment:
      - APP_ENV=production
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: cineverse_db
      MYSQL_USER: cineverse_user
      MYSQL_PASSWORD: password
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"

volumes:
  db_data:
```

### Dockerfile
```dockerfile
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Enable Apache rewrite
RUN a2enmod rewrite

EXPOSE 80
```

## 📊 Monitoring & Maintenance

### Health Monitoring
```bash
# Create monitoring script
#!/bin/bash
# /usr/local/bin/cineverse-health.sh

# Check application health
curl -f http://localhost/health || exit 1

# Check database connection
mysql -u cineverse_user -p'password' -e "SELECT 1" cineverse_db || exit 1

# Check disk space
df -h | awk '$5 > 90 {print $0}' | grep -q . && exit 1

echo "All checks passed"
```

### Backup Strategy
```bash
# Database backup script
#!/bin/bash
# /usr/local/bin/backup-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="cineverse_db"

# Create backup
mysqldump -u cineverse_user -p'password' $DB_NAME > $BACKUP_DIR/cineverse_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/cineverse_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "cineverse_*.sql.gz" -mtime +30 -delete

# Upload to S3 (optional)
aws s3 cp $BACKUP_DIR/cineverse_$DATE.sql.gz s3://your-backup-bucket/
```

### Log Rotation
```bash
# /etc/logrotate.d/cineverse
/var/www/cineverse/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## 🔧 Performance Optimization

### PHP-FPM Configuration
```ini
; /etc/php/8.1/fpm/pool.d/cineverse.conf
[cineverse]
user = www-data
group = www-data
listen = /run/php/php8.1-fpm-cineverse.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
```

### MySQL Optimization
```sql
-- /etc/mysql/mysql.conf.d/cineverse.cnf
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 256M
max_connections = 200
```

### Redis Configuration
```conf
# /etc/redis/redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
sudo chown -R www-data:www-data /var/www/cineverse
sudo chmod -R 755 /var/www/cineverse
sudo chmod -R 775 storage public/uploads
```

#### 2. Database Connection Issues
```bash
# Check MySQL status
sudo systemctl status mysql

# Test connection
mysql -u cineverse_user -p -h localhost cineverse_db
```

#### 3. PHP Errors
```bash
# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Check error logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/www/cineverse/storage/logs/app.log
```

#### 4. SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew --dry-run
```

### Performance Issues
```bash
# Check system resources
htop
df -h
free -h

# Check slow queries
sudo mysqldumpslow /var/log/mysql/mysql-slow.log

# Monitor PHP processes
sudo ps aux | grep php-fpm
```

## 📞 Support

For deployment support:
- Email: devops@cineverse.com
- Documentation: [docs.cineverse.com/deployment](https://docs.cineverse.com/deployment)
- Issues: [GitHub Issues](https://github.com/your-username/cineverse/issues)

---

**Happy Deploying!** 🚀
