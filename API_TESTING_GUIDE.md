# Gutendex API Testing Guide

## Prerequisites
Your API should be accessible at: `http://YOUR_EC2_IP/api/v1/books`
(Replace `YOUR_EC2_IP` with your actual EC2 instance IP address)

## Method 1: Test with curl (Command Line)

### From Your EC2 Server (SSH into it):

```bash
# 1. Basic request - Get first 25 books
curl -X GET "http://localhost/api/v1/books"

# 2. Get books with pagination
curl -X GET "http://localhost/api/v1/books?page=2"

# 3. Filter by language (English and French)
curl -X GET "http://localhost/api/v1/books?language=en,fr"

# 4. Filter by author (case-insensitive partial match)
curl -X GET "http://localhost/api/v1/books?author=shakespeare"

# 5. Filter by title
curl -X GET "http://localhost/api/v1/books?title=adventure"

# 6. Filter by topic (matches subjects or bookshelves)
curl -X GET "http://localhost/api/v1/books?topic=child"

# 7. Filter by MIME type
curl -X GET "http://localhost/api/v1/books?mime_type=text/html,text/plain"

# 8. Filter by Gutenberg ID
curl -X GET "http://localhost/api/v1/books?gutenberg_id=1,2,3"

# 9. Multiple filters combined
curl -X GET "http://localhost/api/v1/books?language=en&topic=child&author=twain"

# 10. Pretty print JSON output (with jq)
curl -X GET "http://localhost/api/v1/books?author=shakespeare" | jq .
```

### From Your Local Machine (Outside EC2):

Make sure your EC2 security group allows inbound HTTP traffic on port 80 from your IP.

```bash
# Replace YOUR_EC2_IP with your actual EC2 IP address
curl -X GET "http://YOUR_EC2_IP/api/v1/books"
curl -X GET "http://YOUR_EC2_IP/api/v1/books?author=shakespeare"
```

## Method 2: Test with Browser

Simply open your browser and navigate to:

```
http://YOUR_EC2_IP/api/v1/books
http://YOUR_EC2_IP/api/v1/books?author=shakespeare
http://YOUR_EC2_IP/api/v1/books?language=en&topic=child
```

## Method 3: Test with Postman

1. Download and install Postman: https://www.postman.com/downloads/
2. Create a new request
3. Set method to **GET**
4. Enter URL: `http://YOUR_EC2_IP/api/v1/books`
5. Go to "Params" tab and add query parameters:
   - Key: `author` | Value: `shakespeare`
   - Key: `language` | Value: `en`
6. Click "Send"

## Method 4: View Swagger Documentation

### Option A - Online Swagger Editor:
1. Go to https://editor.swagger.io/
2. Copy the content from `swagger.yaml` file in the repository
3. Paste it in the left panel
4. Update the server URL to your EC2 IP address
5. Use the "Try it out" button to test endpoints

### Option B - Serve Swagger UI from Your Server:

Install Swagger UI on your EC2 server:

```bash
# SSH into your EC2 instance
ssh -i your-key.pem ubuntu@YOUR_EC2_IP

# Install Swagger UI
cd /var/www
sudo git clone https://github.com/swagger-api/swagger-ui.git
cd swagger-ui/dist

# Copy your swagger.yaml file
sudo cp /var/www/gutendex-api/swagger.yaml .

# Edit index.html to point to your swagger.yaml
sudo nano index.html
# Find the line: url: "https://petstore.swagger.io/v2/swagger.json",
# Replace with: url: "./swagger.yaml",

# Configure Nginx to serve Swagger UI
sudo nano /etc/nginx/sites-available/gutendex
```

Add this location block inside the `server` block:

```nginx
location /swagger {
    alias /var/www/swagger-ui/dist;
    index index.html;
}
```

```bash
# Test and reload Nginx
sudo nginx -t
sudo systemctl reload nginx
```

Now access Swagger UI at: `http://YOUR_EC2_IP/swagger`

## Expected Response Format

```json
{
  "total_count": 54859,
  "books": [
    {
      "title": "The Adventures of Tom Sawyer",
      "authors": [
        {
          "name": "Mark Twain",
          "birth_year": 1835,
          "death_year": 1910
        }
      ],
      "genre": "Children's literature",
      "languages": ["en"],
      "subjects": ["Adventure stories", "Boys"],
      "bookshelves": ["Children's literature"],
      "links": [
        {
          "mime_type": "text/html",
          "url": "https://www.gutenberg.org/files/74/74-h/74-h.htm"
        },
        {
          "mime_type": "text/plain",
          "url": "https://www.gutenberg.org/files/74/74-0.txt"
        }
      ]
    }
  ]
}
```

## Troubleshooting

### API returns 502 Bad Gateway
- Check if PHP-FPM is running: `sudo systemctl status php8.2-fpm`
- Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`

### API returns 500 Internal Server Error
- Check Laravel logs: `tail -f /var/www/gutendex-api/storage/logs/laravel.log`
- Ensure database connection is working: `php artisan tinker` then `\DB::table('books_book')->count()`

### Cannot connect from external IP
- Check EC2 Security Group allows inbound HTTP (port 80)
- Check Nginx is listening on port 80: `sudo netstat -tulpn | grep :80`

## Performance Testing

Test the API with multiple concurrent requests:

```bash
# Install Apache Bench (if not installed)
sudo apt install -y apache2-utils

# Test with 1000 requests, 10 concurrent
ab -n 1000 -c 10 http://localhost/api/v1/books
```

## Deliverables Summary

✅ **1. Swagger Documentation**: Available at `/swagger.yaml` in the repository
✅ **2. Public API**: Accessible at `http://YOUR_EC2_IP/api/v1/books`
✅ **3. Source Code**: Available at https://github.com/Rohi7875/gutendex-api

