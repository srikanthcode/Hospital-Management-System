#!/bin/bash
# Startup script: MariaDB + Apache in one container
# Logs go to stderr so they appear in Render's live logs

echo "=== Lotus Hospital Docker Start ==="

# ── Ensure runtime dirs exist ──────────────────────────────────────
mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld
mkdir -p /var/lib/mysql
chown -R mysql:mysql /var/lib/mysql

# ── Find the right binaries (Debian Bookworm uses mariadb-* names) ─
MARIADBD=$(which mariadbd 2>/dev/null || which mysqld 2>/dev/null)
MARIADB_INSTALL_DB=$(which mariadb-install-db 2>/dev/null || which mysql_install_db 2>/dev/null)
MARIADB_CLIENT=$(which mariadb 2>/dev/null || which mysql 2>/dev/null)
MARIADB_ADMIN=$(which mariadb-admin 2>/dev/null || which mysqladmin 2>/dev/null)

echo "mariadbd:     $MARIADBD"
echo "install-db:   $MARIADB_INSTALL_DB"
echo "client:       $MARIADB_CLIENT"
echo "admin:        $MARIADB_ADMIN"

# ── Initialize data directory if needed ────────────────────────────
if [ ! -d /var/lib/mysql/mysql ]; then
    echo "Initializing MariaDB data directory..."
    $MARIADB_INSTALL_DB --user=mysql --datadir=/var/lib/mysql 2>&1
    echo "Data dir init done."
fi

# ── Start MariaDB daemon ───────────────────────────────────────────
echo "Starting MariaDB daemon..."
$MARIADBD --user=mysql --datadir=/var/lib/mysql \
    --socket=/var/run/mysqld/mysqld.sock --port=3306 \
    --skip-name-resolve &
MYSQL_PID=$!

# ── Wait for socket (up to 60s) ───────────────────────────────────
echo "Waiting for MariaDB socket..."
READY=0
for i in $(seq 1 60); do
    if [ -S /var/run/mysqld/mysqld.sock ]; then
        READY=1
        echo "Socket ready after ${i}s"
        break
    fi
    # Check if mariadbd died
    if ! kill -0 $MYSQL_PID 2>/dev/null; then
        echo "ERROR: mariadbd process died! Exit code: $?"
        break
    fi
    sleep 1
done

if [ "$READY" -eq 0 ]; then
    echo "ERROR: MariaDB socket never appeared after 60s"
    echo "Trying to continue anyway..."
fi

# ── Create database + user ─────────────────────────────────────────
echo "Creating database..."
$MARIADB_CLIENT --socket=/var/run/mysqld/mysqld.sock -u root <<'EOSQL'
CREATE DATABASE IF NOT EXISTS hospital_management
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hospital'@'localhost' IDENTIFIED BY 'hospital_pass';
GRANT ALL PRIVILEGES ON hospital_management.* TO 'hospital'@'localhost';
FLUSH PRIVILEGES;
EOSQL
echo "Database created."

# ── Auto-install schema if tables don't exist ──────────────────────
TABLE_COUNT=$($MARIADB_CLIENT --socket=/var/run/mysqld/mysqld.sock -u root \
    -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hospital_management'" 2>/dev/null || echo "0")

echo "Current table count: $TABLE_COUNT"

if [ "$TABLE_COUNT" -eq 0 ]; then
    echo "First run — installing schema..."
    if [ -f /var/www/html/install/schema.sql ]; then
        $MARIADB_CLIENT --socket=/var/run/mysqld/mysqld.sock -u root hospital_management \
            < /var/www/html/install/schema.sql 2>&1 && echo "schema.sql OK"
    else
        echo "WARNING: /var/www/html/install/schema.sql not found"
    fi
    if [ -f /var/www/html/install/realtime_schema.sql ]; then
        $MARIADB_CLIENT --socket=/var/run/mysqld/mysqld.sock -u root hospital_management \
            < /var/www/html/install/realtime_schema.sql 2>&1 && echo "realtime_schema.sql OK"
    else
        echo "WARNING: /var/www/html/install/realtime_schema.sql not found"
    fi
    echo "Schema installation complete."
else
    echo "Database has $TABLE_COUNT tables — skipping install."
fi

# ── Start Apache in foreground ──────────────────────────────────────
echo "Starting Apache..."
exec apache2-foreground
