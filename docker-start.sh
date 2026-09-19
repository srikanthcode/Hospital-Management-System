#!/bin/bash
set -e

# ── Start MariaDB ──────────────────────────────────────────────────
echo "Starting MariaDB..."
mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
mysqld --user=mysql --datadir=/var/lib/mysql --skip-networking &
MYSQL_PID=$!

# Wait for MariaDB to be ready
for i in $(seq 1 30); do
    if mysqladmin ping --socket=/var/run/mysqld/mysqld.sock > /dev/null 2>&1; then
        break
    fi
    sleep 1
done

# ── Create database + user ─────────────────────────────────────────
echo "Setting up database..."
mysql --socket=/var/run/mysqld/mysqld.sock -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS hospital_management
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'hospital'@'localhost' IDENTIFIED BY 'hospital_pass';
GRANT ALL PRIVILEGES ON hospital_management.* TO 'hospital'@'localhost';
FLUSH PRIVILEGES;
SQL

# ── Auto-install schema if tables don't exist ──────────────────────
TABLE_COUNT=$(mysql --socket=/var/run/mysqld/mysqld.sock -u root \
    -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hospital_management'" 2>/dev/null)

if [ "$TABLE_COUNT" -eq 0 ]; then
    echo "First run — installing database schema..."
    mysql --socket=/var/run/mysqld/mysqld.sock -u root hospital_management \
        < /var/www/html/install/schema.sql 2>/dev/null || true
    mysql --socket=/var/run/mysqld/mysqld.sock -u root hospital_management \
        < /var/www/html/install/realtime_schema.sql 2>/dev/null || true
    echo "Schema installed."
else
    echo "Database already has $TABLE_COUNT tables — skipping install."
fi

# ── Make DB credentials available to PHP via /etc/environment ───────
cat > /etc/environment <<EOF
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=hospital_management
EOF

# ── Start Apache in foreground ──────────────────────────────────────
echo "Starting Apache..."
exec apache2-foreground
