# Deployment Guide

This guide will help you deploy the Gutendex API to a publicly accessible location.

## Deployment Options

### Option 1: Laravel Forge (Recommended)

Laravel Forge is the easiest way to deploy Laravel applications.

1. **Sign up** at [Laravel Forge](https://forge.laravel.com)
2. **Connect your Git repository** (GitHub, GitLab, or Bitbucket)
3. **Create a new server** (DigitalOcean, AWS, Linode, etc.)
4. **Create a new site** and link it to your repository
5. **Configure environment variables** in Forge dashboard
6. **Set up PostgreSQL database** through Forge
7. **Deploy!** Forge will handle the rest

### Option 2: Laravel Vapor (Serverless)

For serverless deployment on AWS:

1. **Install Vapor CLI**
   ```bash
   composer require laravel/vapor-cli --global
   vapor login
   ```

2. **Initialize Vapor**
   ```bash
   vapor init
   ```

3. **Configure** `vapor.yml` for your needs

4. **Deploy**
   ```bash
   vapor deploy production
   ```

### Option 3: Traditional VPS (DigitalOcean, Linode, etc.)

#### Step 1: Set up Server

1. Create a new Ubuntu 22.04 LTS server
2. SSH into your server
3. Update system packages:
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

#### Step 2: Install Required Software

```bash
# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (for npm)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### Step 3: Configure PostgreSQL

```bash
# Create database and user
sudo -u postgres psql
```

In PostgreSQL prompt:
```sql
CREATE DATABASE gutendex;
CREATE USER gutendex_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE gutendex TO gutendex_user;
\q
```

#### Step 4: Deploy Application

```bash
# Clone repository
cd /var/www
sudo git clone <your-repository-url> gutendex-api
cd gutendex-api

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/gutendex-api
sudo chmod -R 755 /var/www/gutendex-api
sudo chmod -R 775 /var/www/gutendex-api/storage
sudo chmod -R 775 /var/www/gutendex-api/bootstrap/cache
```

#### Step 5: Configure Environment

```bash
cp .env.example .env
nano .env
```

Update these values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gutendex
DB_USERNAME=gutendex_user
DB_PASSWORD=your_secure_password
```

Generate application key:
```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

#### Step 6: Import Database

```bash
# Import your PostgreSQL dump
psql -U gutendex_user -d gutendex -f your_database_dump.sql
```

#### Step 7: Configure Nginx

Create `/etc/nginx/sites-available/gutendex-api`:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/gutendex-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/gutendex-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Step 8: Set up SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

#### Step 9: Set up Queue Worker (Optional)

If you use queues, set up a supervisor:

```bash
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/gutendex-worker.conf`:
```ini
[program:gutendex-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gutendex-api/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/gutendex-api/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gutendex-worker:*
```

### Option 4: Docker Deployment

Create `Dockerfile`:
```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .
RUN composer install --optimize-autoloader --no-dev
RUN php artisan config:cache
RUN php artisan route:cache

EXPOSE 9000
CMD ["php-fpm"]
```

Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - .:/var/www
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - .:/var/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: gutendex
      POSTGRES_USER: gutendex_user
      POSTGRES_PASSWORD: your_password
    volumes:
      - postgres_data:/var/lib/postgresql/data

volumes:
  postgres_data:
```

## Post-Deployment Checklist

- [ ] Environment variables configured
- [ ] Database imported and migrations run
- [ ] Application key generated
- [ ] Config and route caching enabled
- [ ] SSL certificate installed
- [ ] Firewall configured (ports 80, 443, 22)
- [ ] Monitoring set up
- [ ] Backup strategy in place
- [ ] API tested and working

## Testing Your Deployment

Test the API endpoint:
```bash
curl https://your-domain.com/api/v1/books
```

Or visit in browser:
```
https://your-domain.com/api/v1/books
```

## Monitoring

Consider setting up:
- **Laravel Telescope** (for development/staging)
- **Sentry** (for error tracking)
- **Uptime monitoring** (UptimeRobot, Pingdom)
- **Log aggregation** (Papertrail, Loggly)

## Backup Strategy

Set up automated backups:
```bash
# Add to crontab
0 2 * * * pg_dump -U gutendex_user gutendex > /backups/gutendex_$(date +\%Y\%m\%d).sql
```

## Security Considerations

1. **Keep dependencies updated**: `composer update`
2. **Use strong database passwords**
3. **Enable firewall** (UFW)
4. **Regular security updates**: `sudo apt update && sudo apt upgrade`
5. **Use HTTPS only**
6. **Set proper file permissions**
7. **Review Laravel security best practices**

