#!/bin/sh
set -e

# Fix git ownership issue
git config --global --add safe.directory /var/www

# Debug: Print environment variables
echo "Current environment variables:"
echo "DB_HOST: ${DB_HOST:-not set}"
echo "DB_PORT: ${DB_PORT:-not set}"
echo "DB_NAME: ${DB_NAME:-not set}"
echo "DB_USER: ${DB_USER:-not set}"
echo "DB_PASS: ${DB_PASS:-not set}"

# Wait for database to be ready with better feedback
echo "Waiting for database to be ready..."
MAX_RETRIES=30
RETRY_COUNT=0

# Function to check if MySQL is ready using PHP
check_mysql() {
    # Try to connect with PHP PDO
    if ! php -r "
        try {
            \$pdo = new PDO(
                'mysql:host=${DB_HOST:-db};port=${DB_PORT:-3306};dbname=${DB_NAME:-rss_reader}',
                '${DB_USER:-rss_reader}',
                '${DB_PASS:-rss_reader}',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            \$pdo->query('SELECT 1');
            exit(0);
        } catch (PDOException \$e) {
            echo 'Connection error: ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
    "; then
        return 1
    fi
    
    return 0
}

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if check_mysql; then
        echo "Database is accepting connections and ready for queries!"
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "Attempt $RETRY_COUNT/$MAX_RETRIES: Database not ready yet, waiting..."
    sleep 2
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "Error: Could not connect to database after $MAX_RETRIES attempts"
    exit 1
fi

echo "Database is ready!"

# Run migrations
php resources/migrations/run.php

# Install frontend dependencies and start frontend
cd /var/www/frontend
npm install
npm run start &

# Start PHP server
cd /var/www
php -S 0.0.0.0:8080 -t public 