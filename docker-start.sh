#!/bin/bash
set -e

# ── Start MariaDB ──────────────────────────────────────────────────
echo "[start] MariaDB — initializing data directory..."
mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld

# Initialize only if data directory is empty
if [ ! -d /var/lib/mysql/mysql ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

echo "[start] MariaDB — starting daemon..."
mysqld --user=mysql --datadir=/var/lib/mysql --socket=/var/run/mysqld/mysqld.sock --port=3306 &
MYSQL_PID=$!

# Wait for the socket to appear (up to 60 seconds)
echo "[start] Waiting for MariaDB socket..."
for i in $(seq 1 60); do
    if [ -S /var/run/mysqld/mysqld.sock ]; then
        echo "[start] MariaDB socket ready after ${i}s"
        break
    fi
    sleep 1
done

# Extra safety — wait for actual ping
for i in $(seq 1 30); do
    if mysqladmin ping --socket=/var/run/mysqld/mysqld.sock -u root > /dev/null 2>&1; then
        echo "[start] MariaDB ping OK"
        break
    fi
    sleep 1
done

# ── Create database + user ─────────────────────────────────────────
echo "[start] Creating database..."
mysql --socket=/var/run/mysqld/mysqld.sock -u root <<'EOSQL'
CREATE DATABASE IF NOT EXISTS hospital_management
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hospital'@'localhost' IDENTIFIED BY 'hospital_pass';
GRANT ALL PRIVILEGES ON hospital_management.* TO 'hospital'@'localhost';
FLUSH PRIVILEGES;
EOSQL

# ── Auto-install schema if tables don't exist ──────────────────────
TABLE_COUNT=$(mysql --socket=/var/run/mysqld/mysqld.sock -u root \
    -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hospital_management'" 2>/dev/null || echo "0")

if [ "$TABLE_COUNT" -eq 0 ]; then
    echo "[start] First run — installing schema..."
    if [ -f /var/www/html/install/schema.sql ]; then
        mysql --socket=/var/run/mysqld/mysqld.sock -u root hospital_management \
            < /var/www/html/install/schema.sql && echo "[start] schema.sql OK" || echo "[start] schema.sql had errors (may be partial)"
    fi
    if [ -f /var/www/html/install/realtime_schema.sql ]; then
        mysql --socket=/var/run/mysqld/mysqld.sock -u root hospital_management \
            < /var/www/html/install/realtime_schema.sql && echo "[start] realtime_schema.sql OK" || echo "[start] realtime_schema.sql had errors"
    fi
    echo "[start] Schema installation complete."
else
    echo "[start] Database has $TABLE_COUNT tables — skipping install."
fi

# ── Start Apache in foreground ──────────────────────────────────────
echo "[start] Starting Apache..."
exec apache2-foreground
