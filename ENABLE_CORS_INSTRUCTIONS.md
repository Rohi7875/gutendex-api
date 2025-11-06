# Enable CORS for Swagger Editor Access

## Problem
Swagger Editor (https://editor.swagger.io) shows "loading" indefinitely because your API doesn't allow cross-origin requests (CORS). Your API works fine when accessed directly in a browser, but fails when accessed from Swagger Editor's domain due to browser security restrictions.

## Solution
I've added CORS configuration to allow requests from any origin (including Swagger Editor).

## Deploy the CORS Fix to Your EC2 Server

SSH into your EC2 instance and run these commands:

```bash
# SSH into your server
ssh -i your-key.pem ubuntu@13.126.242.247

# Navigate to your project directory
cd /var/www/gutendex-api

# Pull the latest changes
git pull origin main

# Clear Laravel config cache to load new CORS config
php artisan config:clear

# Restart PHP-FPM to apply changes
sudo systemctl restart php8.2-fpm

# Restart Nginx
sudo systemctl restart nginx
```

## Verify CORS is Working

After deploying, test with curl to see CORS headers:

```bash
curl -I -X OPTIONS http://13.126.242.247/api/v1/books \
  -H "Origin: https://editor.swagger.io" \
  -H "Access-Control-Request-Method: GET"
```

You should see response headers like:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
```

## Files Changed

1. **config/cors.php** (NEW)
   - Added CORS configuration
   - Allows all origins (`'allowed_origins' => ['*']`)
   - Allows all methods and headers

2. **bootstrap/app.php** (MODIFIED)
   - Added `HandleCors` middleware to API routes
   - Ensures CORS headers are sent with every API response

## Test Swagger Editor Again

After deploying these changes:

1. Go to https://editor.swagger.io/
2. Copy your swagger.yaml content
3. Paste it in the left panel
4. The "Try it out" button should now work!
5. You should see proper responses instead of "loading"

## Alternative: Test Without Swagger Editor

If you don't want to enable CORS for all origins, you can still test your API using:

### Option 1: Browser Extensions
- Install a CORS extension like "CORS Unblock" (not recommended for production)

### Option 2: Serve Swagger UI Locally on Your Server

```bash
# SSH into your server
cd /var/www

# Clone Swagger UI
sudo git clone https://github.com/swagger-api/swagger-ui.git
cd swagger-ui/dist

# Copy your swagger.yaml
sudo cp /var/www/gutendex-api/swagger.yaml .

# Edit index.html to point to your local swagger.yaml
sudo nano index.html
```

Find this line in `index.html`:
```javascript
url: "https://petstore.swagger.io/v2/swagger.json",
```

Replace with:
```javascript
url: "./swagger.yaml",
```

Save and exit (Ctrl+X, Y, Enter)

```bash
# Configure Nginx to serve Swagger UI
sudo nano /etc/nginx/sites-available/gutendex
```

Add this location block inside your server block (before the closing `}`):

```nginx
location /swagger/ {
    alias /var/www/swagger-ui/dist/;
    index index.html;
    try_files $uri $uri/ =404;
}
```

```bash
# Test and reload Nginx
sudo nginx -t
sudo systemctl reload nginx
```

Now access Swagger UI at: **http://13.126.242.247/swagger/**

This way, Swagger UI and your API are on the same domain, so no CORS issues!

## Security Note

The current CORS configuration allows requests from ANY origin (`'*'`). This is fine for a public API. If you want to restrict it later, edit `config/cors.php`:

```php
'allowed_origins' => [
    'https://editor.swagger.io',
    'http://13.126.242.247',
    // Add other allowed domains
],
```

Then run `php artisan config:clear` and restart PHP-FPM.

