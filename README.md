# CineVerse - Production-Ready Movie Web Application

A comprehensive, production-ready movie discovery web application built with PHP, featuring user authentication, payment integration, multi-language support, and a complete admin dashboard.

## 🚀 Features

### Core Features
- **User Authentication System**
  - Registration and login
  - Password reset functionality
  - Email verification
  - Session management
  - JWT token support for API

- **Movie Management**
  - Integration with TMDB API
  - Advanced search and filtering
  - User watchlists and ratings
  - Movie recommendations
  - Genre-based browsing

- **Payment Integration**
  - MTN Mobile Money integration
  - Airtel Money integration
  - Subscription management
  - Transaction tracking

- **Multi-language Support**
  - English, Kinyarwanda, French
  - Dynamic language switching
  - Admin translation management

- **Admin Dashboard**
  - User management
  - Movie management
  - Analytics and reporting
  - System settings
  - Payment monitoring

### Technical Features
- **Modern PHP Architecture**
  - PHP 8.1+ with modern features
  - MVC pattern implementation
  - Dependency injection
  - Middleware support

- **Database**
  - MySQL/PostgreSQL support
  - Query builder
  - Migration system
  - Proper indexing and relationships

- **API-First Design**
  - RESTful API endpoints
  - JSON responses
  - Mobile app ready
  - CORS support

- **Security**
  - Password hashing
  - CSRF protection
  - Rate limiting
  - Input validation
  - SQL injection prevention

- **Performance**
  - Caching system
  - Database optimization
  - Asset optimization
  - CDN ready

## 📋 Requirements

- PHP 8.1 or higher
- MySQL 8.0+ or PostgreSQL 13+
- Composer
- Web server (Apache/Nginx)
- SSL certificate (recommended)

### PHP Extensions
- PDO
- JSON
- cURL
- OpenSSL
- Mbstring
- GD or Imagick

## 🛠 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/cineverse.git
cd cineverse
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
```

Edit the `.env` file with your configuration:
```env
# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cineverse_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# JWT Secret
JWT_SECRET=your-super-secret-jwt-key

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Payment Gateways
MTN_PRIMARY_KEY=your-mtn-primary-key
AIRTEL_CLIENT_ID=your-airtel-client-id

# API Keys
TMDB_API_KEY=your-tmdb-api-key
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE cineverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate
```

Or import the schema directly:
```bash
mysql -u username -p cineverse_db < database/schema.sql
```

### 5. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

#### Nginx
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

### 6. Set Permissions
```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
chown -R www-data:www-data storage/
chown -R www-data:www-data public/uploads/
```

## 🔧 Configuration

### Payment Gateways

#### MTN Mobile Money
1. Register at [MTN Developer Portal](https://momodeveloper.mtn.com/)
2. Create a new application
3. Get your API keys and update `.env`

#### Airtel Money
1. Register at [Airtel Developer Portal](https://developers.airtel.africa/)
2. Create a new application
3. Get your credentials and update `.env`

### External APIs

#### TMDB (The Movie Database)
1. Register at [TMDB](https://www.themoviedb.org/)
2. Get your API key
3. Update `TMDB_API_KEY` in `.env`

### Email Configuration
Configure SMTP settings in `.env` for:
- User registration emails
- Password reset emails
- Notifications

## 📱 API Documentation

### Authentication Endpoints
```
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
GET  /api/v1/auth/me
```

### Movie Endpoints
```
GET  /api/v1/movies
GET  /api/v1/movies/search
GET  /api/v1/movies/trending
GET  /api/v1/movies/{id}
POST /api/v1/movies/{id}/watchlist
POST /api/v1/movies/{id}/rating
```

### Payment Endpoints
```
GET  /api/v1/payment/plans
POST /api/v1/payment/subscribe
GET  /api/v1/payment/transactions
```

## 🎨 Frontend Integration

The application provides a complete API that can be consumed by:
- React/Vue.js SPAs
- Mobile applications (React Native, Flutter)
- Third-party integrations

### Example API Usage
```javascript
// Login
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        identifier: 'user@example.com',
        password: 'password123'
    })
});

const data = await response.json();
const token = data.data.token;

// Get movies
const movies = await fetch('/api/v1/movies', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});
```

## 🔒 Security Features

- **Password Security**: Bcrypt hashing with salt
- **Session Security**: Secure session handling
- **CSRF Protection**: Token-based CSRF protection
- **Rate Limiting**: API rate limiting
- **Input Validation**: Comprehensive input validation
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Output escaping

## 📊 Analytics Integration

### Google Analytics
Add your Google Analytics ID to `.env`:
```env
GOOGLE_ANALYTICS_ID=GA-XXXXXXXXX-X
```

### Custom Analytics
The application tracks:
- User engagement
- Movie views
- Search queries
- Payment conversions

## 🌍 Multi-language Support

### Supported Languages
- English (en)
- Kinyarwanda (rw)
- French (fr)

### Adding New Languages
1. Add language code to `SUPPORTED_LANGUAGES` in `.env`
2. Create translation files
3. Update admin panel translations

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Configure SSL certificate
- [ ] Set up database backups
- [ ] Configure email service
- [ ] Set up monitoring
- [ ] Configure caching (Redis recommended)
- [ ] Set up CDN for assets

### Cloud Deployment

#### AWS
- Use EC2 for application server
- RDS for database
- S3 for file storage
- CloudFront for CDN

#### DigitalOcean
- Use Droplets for application
- Managed Database for MySQL
- Spaces for file storage

#### Shared Hosting
- Upload files to public_html
- Import database
- Update file permissions

## 🧪 Testing

```bash
# Run tests
composer test

# Code style check
composer cs-check

# Static analysis
composer analyze
```

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📞 Support

For support and questions:
- Email: support@cineverse.com
- Documentation: [docs.cineverse.com](https://docs.cineverse.com)
- Issues: [GitHub Issues](https://github.com/your-username/cineverse/issues)

## 🎯 Roadmap

- [ ] Mobile applications (iOS/Android)
- [ ] Social features (friends, sharing)
- [ ] Advanced recommendations (ML-based)
- [ ] Video streaming integration
- [ ] Offline mode support
- [ ] Progressive Web App (PWA)

---

**CineVerse** - The Future of Movie Discovery 🎬
