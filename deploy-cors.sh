#!/bin/bash

# Gutendex API - Deploy CORS Fix
# Run this script on your EC2 server to enable CORS

echo "=========================================="
echo "Deploying CORS Fix to Gutendex API"
echo "=========================================="
echo ""

# Navigate to project directory
cd /var/www/gutendex-api

echo "1. Pulling latest changes from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "ERROR: Git pull failed!"
    echo "Try running: sudo chown -R ubuntu:ubuntu /var/www/gutendex-api"
    exit 1
fi

echo ""
echo "2. Clearing Laravel config cache..."
php artisan config:clear

echo ""
echo "3. Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm

echo ""
echo "4. Restarting Nginx..."
sudo systemctl restart nginx

echo ""
echo "=========================================="
echo "✅ CORS Fix Deployed Successfully!"
echo "=========================================="
echo ""
echo "Your API now supports cross-origin requests."
echo "Swagger Editor should work now!"
echo ""
echo "Test it at: https://editor.swagger.io/"
echo ""

