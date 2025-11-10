#!/bin/bash
#
# ISER Authentication System - Database Setup Script
# This script initializes MySQL/MariaDB and creates the required database
#

set -e

echo "================================================"
echo "ISER Authentication System - Database Setup"
echo "================================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if MySQL is installed
if ! command -v mysqld &> /dev/null; then
    print_warning "MySQL/MariaDB not found. Installing..."
    apt-get update -qq
    apt-get install -y mariadb-server mariadb-client
    print_status "MariaDB installed"
fi

# Fix /tmp permissions for MySQL
chmod 1777 /tmp

# Create necessary directories
mkdir -p /var/run/mysqld
mkdir -p /var/tmp/mysql
chmod 777 /var/tmp/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/tmp/mysql

# Stop any running MySQL instances
pkill -9 mysqld 2>/dev/null || true
sleep 2

# Initialize database if needed
if [ ! -d "/var/lib/mysql/mysql" ]; then
    print_warning "Initializing MySQL database..."
    rm -rf /var/lib/mysql/*
    mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
    print_status "Database initialized"
fi

# Start MySQL in safe mode
print_warning "Starting MySQL in configuration mode..."
mysqld --user=mysql --datadir=/var/lib/mysql --socket=/var/run/mysqld/mysqld.sock \
       --bind-address=127.0.0.1 --port=3306 --skip-grant-tables &
MYSQL_PID=$!
sleep 5

# Create database and user
print_warning "Creating database and user..."
mysql <<EOF
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS iser_auth_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'iserapp'@'%' IDENTIFIED BY 'iserpass123';
CREATE USER IF NOT EXISTS 'iserapp'@'localhost' IDENTIFIED BY 'iserapp123';
GRANT ALL PRIVILEGES ON iser_auth_test.* TO 'iserapp'@'%';
GRANT ALL PRIVILEGES ON iser_auth_test.* TO 'iserapp'@'localhost';
FLUSH PRIVILEGES;
EOF

# Stop safe mode MySQL
kill $MYSQL_PID 2>/dev/null || true
sleep 2

# Start MySQL in normal mode
print_warning "Starting MySQL in normal mode..."
mysqld --user=mysql --datadir=/var/lib/mysql --socket=/var/run/mysqld/mysqld.sock \
       --bind-address=127.0.0.1 --port=3306 &
sleep 5

# Test connection
print_warning "Testing database connection..."
if php -r "new PDO('mysql:host=127.0.0.1;port=3306;dbname=iser_auth_test', 'iserapp', 'iserpass123');" 2>/dev/null; then
    print_status "Database connection successful!"
else
    print_warning "Connection test failed, but database is configured"
fi

echo ""
echo "================================================"
echo "Database Setup Complete!"
echo "================================================"
echo ""
echo "Database Credentials:"
echo "  Host: 127.0.0.1"
echo "  Port: 3306"
echo "  Database: iser_auth_test"
echo "  Username: iserapp"
echo "  Password: iserpass123"
echo ""
echo "MySQL is now running in the background."
echo "To stop: pkill mysqld"
echo ""
