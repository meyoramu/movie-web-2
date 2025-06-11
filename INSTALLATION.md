# CineVerse Installation Guide

Welcome to CineVerse! This guide will help you install and set up your production-ready movie web application.

## 🚀 Quick Start

### Option 1: Automated Installation (Recommended)

1. **Upload files to your server**
2. **Navigate to your domain**
3. **Run the installation wizard**
   ```
   https://your-domain.com/install.php
   ```
4. **Follow the step-by-step wizard**

### Option 2: Manual Installation

#### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0+ or PostgreSQL 13+
- Composer
- Web server (Apache/Nginx)

#### Step 1: Download and Extract
```bash
# Download the latest release
wget https://github.com/your-username/cineverse/archive/main.zip
unzip main.zip
cd cineverse-main
```

#### Step 2: Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

#### Step 3: Environment Configuration
```bash
cp .env.example .env
nano .env
```

Configure your `.env` file:
```env
# Application
APP_NAME="CineVerse"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cineverse_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Security
JWT_SECRET=your-super-secret-jwt-key-32-chars
ENCRYPTION_KEY=your-32-character-encryption-key

# Email (SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Payment Gateways
MTN_PRIMARY_KEY=your-mtn-primary-key
AIRTEL_CLIENT_ID=your-airtel-client-id

# API Keys
TMDB_API_KEY=your-tmdb-api-key
```

#### Step 4: Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE cineverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u username -p cineverse_db < database/schema.sql
```

#### Step 5: Set Permissions
```bash
chmod -R 755 .
chmod -R 775 storage public/uploads
chown -R www-data:www-data .
```

#### Step 6: Web Server Configuration

**Apache (.htaccess is included)**
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/cineverse/public
    
    <Directory /path/to/cineverse/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/cineverse/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🐳 Docker Installation

### Quick Docker Setup
```bash
# Clone repository
git clone https://github.com/your-username/cineverse.git
cd cineverse

# Copy environment file
cp .env.example .env

# Start with Docker Compose
docker-compose up -d

# Access your application
open http://localhost
```

### Production Docker
```bash
# Build production image
docker build -t cineverse:latest .

# Run with production settings
docker run -d \
  --name cineverse \
  -p 80:80 \
  -p 443:443 \
  -e APP_ENV=production \
  -e DB_HOST=your-db-host \
  -v ./storage:/var/www/html/storage \
  cineverse:latest
```

## ☁️ Cloud Deployment

### AWS EC2
```bash
# Launch Ubuntu 22.04 instance
# Install LAMP stack
sudo apt update
sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql composer

# Deploy application
cd /var/www
sudo git clone https://github.com/your-username/cineverse.git
cd cineverse
sudo composer install --no-dev
sudo chown -R www-data:www-data .
```

### DigitalOcean Droplet
```bash
# Create Ubuntu droplet
# Use the one-click LAMP stack
# Upload and configure application
```

### Shared Hosting (cPanel)
1. Create MySQL database via cPanel
2. Upload files to public_html
3. Import database/schema.sql
4. Configure .env file
5. Set file permissions

## 🔧 Configuration

### Payment Gateways

#### MTN Mobile Money
1. Register at [MTN Developer Portal](https://momodeveloper.mtn.com/)
2. Create application and get API keys
3. Update `.env` with your credentials

#### Airtel Money
1. Register at [Airtel Developer Portal](https://developers.airtel.africa/)
2. Create application and get credentials
3. Update `.env` with your credentials

### External APIs

#### TMDB (The Movie Database)
1. Register at [TMDB](https://www.themoviedb.org/)
2. Get API key from settings
3. Add to `.env` as `TMDB_API_KEY`

### Email Configuration
Configure SMTP in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

## 🔒 Security Setup

### SSL Certificate
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable

# iptables
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

## 📊 Post-Installation

### Create Admin User
Access `/admin` and use the credentials you set during installation.

### Configure Settings
1. Go to Admin Panel → Settings
2. Configure site settings
3. Set up payment plans
4. Configure email templates

### Import Movies
1. Go to Admin Panel → Movies
2. Click "Sync from TMDB"
3. Import popular movies

### Test Payment Integration
1. Go to Payment Plans
2. Test MTN/Airtel Money integration
3. Verify webhook endpoints

## 🧪 Testing

### Health Check
```bash
curl https://your-domain.com/health
```

### API Testing
```bash
# Test authentication
curl -X POST https://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"admin@example.com","password":"password"}'

# Test movies endpoint
curl https://your-domain.com/api/v1/movies
```

## 🔧 Troubleshooting

### Common Issues

#### Permission Errors
```bash
sudo chown -R www-data:www-data /path/to/cineverse
sudo chmod -R 755 /path/to/cineverse
sudo chmod -R 775 storage public/uploads
```

#### Database Connection
```bash
# Test connection
mysql -u username -p -h localhost database_name

# Check PHP PDO
php -m | grep pdo
```

#### Apache/Nginx Issues
```bash
# Check Apache status
sudo systemctl status apache2

# Check Nginx status
sudo systemctl status nginx

# Check error logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/nginx/error.log
```

### Performance Optimization

#### Enable OPcache
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

#### Configure MySQL
```sql
-- my.cnf
[mysqld]
innodb_buffer_pool_size = 1G
query_cache_type = 1
query_cache_size = 256M
```

## 📞 Support

### Getting Help
- **Documentation**: [docs.cineverse.com](https://docs.cineverse.com)
- **Issues**: [GitHub Issues](https://github.com/your-username/cineverse/issues)
- **Email**: support@cineverse.com

### Community
- **Discord**: [Join our Discord](https://discord.gg/cineverse)
- **Forum**: [Community Forum](https://forum.cineverse.com)

## 🎯 Next Steps

After installation:
1. ✅ Configure payment gateways
2. ✅ Set up email service
3. ✅ Import movie data
4. ✅ Configure SSL certificate
5. ✅ Set up monitoring
6. ✅ Configure backups
7. ✅ Test all features

## 📈 Scaling

### Load Balancing
- Use multiple app servers
- Configure database replication
- Implement Redis clustering

### CDN Setup
- Configure CloudFlare or AWS CloudFront
- Optimize image delivery
- Cache static assets

### Monitoring
- Set up application monitoring
- Configure log aggregation
- Implement health checks

---

**Congratulations!** 🎉 Your CineVerse application is now ready for production use!
