#!/bin/bash

# Test CORS Configuration

echo "=========================================="
echo "Testing CORS Configuration"
echo "=========================================="
echo ""

# Test 1: Check if config/cors.php exists
echo "1. Checking if config/cors.php exists..."
if [ -f /var/www/gutendex-api/config/cors.php ]; then
    echo "   ✅ config/cors.php exists"
else
    echo "   ❌ config/cors.php NOT FOUND!"
fi

echo ""
echo "2. Testing CORS headers with OPTIONS request..."
curl -i -X OPTIONS http://localhost/api/v1/books \
  -H "Origin: https://editor.swagger.io" \
  -H "Access-Control-Request-Method: GET" \
  2>&1 | grep -i "access-control"

echo ""
echo "3. Testing regular GET request for CORS headers..."
curl -i http://localhost/api/v1/books \
  -H "Origin: https://editor.swagger.io" \
  2>&1 | head -20 | grep -i "access-control"

echo ""
echo "4. Checking Laravel logs for errors..."
tail -20 /var/www/gutendex-api/storage/logs/laravel.log

echo ""
echo "=========================================="
echo "Test Complete"
echo "=========================================="

