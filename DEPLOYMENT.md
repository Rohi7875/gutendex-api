# Deployment Guide - Ubuntu AWS EC2

This guide will help you deploy the Gutendex API on Ubuntu AWS EC2 instance.

## Prerequisites

- AWS account
- Domain name (optional, can use EC2 public IP)
- SSH access to EC2 instance

## Step 1: Set up AWS EC2 Instance

1. **Launch EC2 Instance**
   - Go to AWS Console → EC2 → Launch Instance
   - Choose **Ubuntu Server 22.04 LTS**
   - Select instance type (t3.small or larger recommended)
   - Configure security group:
     - SSH (22) from your IP
     - HTTP (80) from anywhere (0.0.0.0/0)
     - HTTPS (443) from anywhere (0.0.0.0/0)
   - Create or select a key pair
   - Launch instance

2. **Allocate Elastic IP (Recommended)**
   - EC2 → Elastic IPs → Allocate Elastic IP
   - Associate with your instance

3. **Connect to Instance**
   ```bash
   ssh -i your-key.pem ubuntu@your-ec2-ip
   ```

## Step 2: Install Required Software

Update system and install dependencies:

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Add PHP repository (required for PHP 8.2)
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (optional, for npm)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Git
sudo apt install -y git
```

## Step 3: Configure PostgreSQL

```bash
# Create database and user
sudo -u postgres psql
```

In PostgreSQL prompt:
```sql
CREATE DATABASE gutendex;
CREATE USER gutendex_user WITH PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE gutendex TO gutendex_user;
ALTER USER gutendex_user CREATEDB;
\q
```

## Step 4: Deploy Application

```bash
# Clone repository (as ubuntu user, not sudo)
cd /var/www
sudo mkdir -p gutendex-api
sudo chown $USER:$USER gutendex-api
git clone https://github.com/Rohi7875/gutendex-api.git gutendex-api
cd gutendex-api

# Install dependencies (run as current user, not sudo)
composer install --optimize-autoloader --no-dev

# Set proper permissions after installation
sudo chown -R www-data:www-data /var/www/gutendex-api
sudo chmod -R 755 /var/www/gutendex-api
sudo chmod -R 775 /var/www/gutendex-api/storage
sudo chmod -R 775 /var/www/gutendex-api/bootstrap/cache
```

## Step 5: Configure Environment

```bash
cd /var/www/gutendex-api
cp .env.example .env
sudo nano .env
```

Update these values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-ec2-ip-or-domain

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gutendex
DB_USERNAME=gutendex_user
DB_PASSWORD=your_secure_password_here
```

Generate application key and cache config:
```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

## Step 6: Import Database

```bash
# Upload your database dump file to the server first (using scp or sftp)
# Example: scp -i your-key.pem gutendex.dump ubuntu@your-ec2-ip:/var/www/gutendex-api/

# Check the dump file format first:
# - If it's a text format (starts with SQL commands), use psql
# - If it's a custom format (binary), use pg_restore

# For text format dumps (.dump or .sql files with SQL text):
# Option 1: Use TCP/IP connection (will prompt for password)
psql -h localhost -U gutendex_user -d gutendex -f gutendex.dump

# Option 2: Use postgres superuser (no password needed)
# sudo -u postgres psql -d gutendex -f gutendex.dump

# For custom format dumps (binary .dump files created with pg_dump -Fc):
# pg_restore -U gutendex_user -d gutendex -v gutendex.dump

# If you don't have a dump file, run migrations instead:
php artisan migrate
```

**Note**: Most `.dump` files are actually text format SQL dumps. Use `psql` for text format and `pg_restore` only for custom/binary format dumps.

## Step 7: Configure Nginx

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/gutendex-api
```

Add this configuration:
```nginx
server {
    listen 80;
    server_name your-ec2-ip-or-domain;
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
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## Step 8: Configure Firewall (UFW)

```bash
# Allow SSH, HTTP, and HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## Step 9: Set up SSL (Let's Encrypt) - Optional but Recommended

If you have a domain name:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot will automatically configure Nginx for HTTPS.

## Step 10: Test the API

```bash
# Test locally on server
curl http://localhost/api/v1/books

# Test from your machine
curl http://your-ec2-ip/api/v1/books
```

## Post-Deployment Checklist

- [ ] Environment variables configured in `.env`
- [ ] Database imported or migrations run
- [ ] Application key generated
- [ ] Config and route caching enabled
- [ ] Nginx configured and running
- [ ] PHP-FPM running: `sudo systemctl status php8.2-fpm`
- [ ] PostgreSQL running: `sudo systemctl status postgresql`
- [ ] Firewall configured
- [ ] SSL certificate installed (if using domain)
- [ ] API tested and working

## Useful Commands

```bash
# Check Nginx status
sudo systemctl status nginx

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check PostgreSQL status
sudo systemctl status postgresql

# View Nginx error logs
sudo tail -f /var/log/nginx/error.log

# View Laravel logs
tail -f /var/www/gutendex-api/storage/logs/laravel.log

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

## Security Considerations

1. **Keep system updated**: `sudo apt update && sudo apt upgrade`
2. **Use strong database passwords**
3. **Firewall enabled** (UFW)
4. **Use HTTPS** (Let's Encrypt)
5. **Regular backups** of database
6. **Keep Laravel updated**: `composer update`
7. **Restrict SSH access** to specific IPs in security group

## Backup Strategy

Set up automated database backups:

```bash
# Create backup directory
sudo mkdir -p /backups

# Add to crontab (edit with: crontab -e)
0 2 * * * pg_dump -U gutendex_user gutendex > /backups/gutendex_$(date +\%Y\%m\%d).sql
```

## Troubleshooting

**API not accessible:**
- Check security group allows HTTP/HTTPS
- Check Nginx is running: `sudo systemctl status nginx`
- Check Nginx config: `sudo nginx -t`
- Check Laravel logs: `tail -f storage/logs/laravel.log`

**Database connection errors:**
- Verify PostgreSQL is running: `sudo systemctl status postgresql`
- Check `.env` database credentials
- Test connection: `psql -U gutendex_user -d gutendex`

**Permission errors:**
- Fix storage permissions: `sudo chmod -R 775 storage bootstrap/cache`
- Fix ownership: `sudo chown -R www-data:www-data /var/www/gutendex-api`
