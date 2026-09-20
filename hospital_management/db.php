<?php
/**
 * Database connection — Render PostgreSQL.
 *
 * The app talks to the database through the mysqli_* compatibility layer in
 * includes/mysqli_compat.php, which is implemented on top of PDO + pdo_pgsql.
 *
 * Credentials are read from environment variables when set (recommended — set
 * them in the Render web service dashboard), otherwise the Render PostgreSQL
 * values below are used so the app works with zero configuration.
 *
 * The schema is created automatically on the first request after a fresh
 * database is pointed at, so no manual install step is required.
 */

require_once __DIR__ . '/includes/mysqli_compat.php';

/**
 * Resolve the PostgreSQL connection settings from environment variables only.
 * No credentials are stored in this repository.
 *
 * Preferred: set DATABASE_URL (or POSTGRES_URL) on the Render web service to the
 * database's Internal Database URL, e.g.
 *     postgresql://user:pass@dpg-xxx-a/hospital_e8tc
 * Alternatively set the individual PGHOST / PGPORT / PGDATABASE / PGUSER /
 * PGPASSWORD variables.
 */
function pg_connection_config()
{
    $url = getenv('DATABASE_URL');
    if ($url === false || $url === '') {
        $url = getenv('POSTGRES_URL');
    }
    if (!empty($url) && preg_match('#^postgres(?:ql)?://([^:@/]+)(?::([^@/]*))?@([^:/]+)(?::(\d+))?/(.+)$#', $url, $m)) {
        return [
            'host'    => $m[3],
            'port'    => (int)($m[4] === '' ? 5432 : $m[4]),
            'db'      => $m[5],
            'user'    => $m[1],
            'pass'    => $m[2],
            'sslmode' => 'require',
        ];
    }

    if (getenv('PGHOST') !== false || getenv('PGDATABASE') !== false || getenv('PGUSER') !== false) {
        return [
            'host'    => getenv('PGHOST')     !== false ? getenv('PGHOST')     : 'localhost',
            'port'    => (int)(getenv('PGPORT') !== false ? getenv('PGPORT') : 5432),
            'db'      => getenv('PGDATABASE') !== false ? getenv('PGDATABASE') : 'hospital_management',
            'user'    => getenv('PGUSER')     !== false ? getenv('PGUSER')     : 'hospital',
            'pass'    => getenv('PGPASSWORD') !== false ? getenv('PGPASSWORD') : '',
            'sslmode' => getenv('PGSSLMODE')  !== false ? getenv('PGSSLMODE')  : 'require',
        ];
    }

    http_response_code(503);
    header('Content-Type: text/plain');
    die("Database is not configured.\n"
        . "Set DATABASE_URL (the Internal Database URL from your Render PostgreSQL)\n"
        . "in the Environment tab of this Render web service, then redeploy.\n"
        . "See: https://render.com/docs/databases#connecting");
}

/**
 * Split a .sql script into individual statements. The bundled schema contains
 * no semicolons inside string literals, so a simple split is safe.
 */
function pg_split_sql($sql)
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string)$sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            continue;
        }
        $out[] = $line;
    }
    $joined = implode("\n", $out);
    foreach (explode(';', $joined) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            yield $stmt;
        }
    }
}

/**
 * Create the tables/seed data the first time an empty database is used.
 * Guarded by an advisory lock so concurrent cold-start requests cannot race.
 */
function pg_bootstrap_schema(PgConnection $conn)
{
    // Per-instance marker: once the schema is known to exist, skip the checks.
    $marker = sys_get_temp_dir() . '/lotus_pg_schema_ok';
    if (is_file($marker)) {
        return;
    }

    $pdo = $conn->pdo;
    $pdo->beginTransaction();
    try {
        $pdo->query('SELECT pg_advisory_xact_lock(1)');
        $row = $pdo->query("SELECT to_regclass('public.users') AS c")->fetch(PDO::FETCH_ASSOC);
        if (empty($row['c'])) {
            $files = ['schema.sql', 'realtime_schema.sql'];
            foreach ($files as $f) {
                $path = __DIR__ . '/install/' . $f;
                if (!is_file($path)) {
                    continue;
                }
                foreach (pg_split_sql(file_get_contents($path)) as $stmt) {
                    $pdo->exec($stmt);
                }
            }
        }
        $pdo->commit();
        @file_put_contents($marker, "1\n");
    } catch (Exception $e) {
        try { $pdo->rollBack(); } catch (Exception $ignore) {}
        http_response_code(503);
        header('Content-Type: text/plain');
        die("Database schema bootstrap failed.\n" . $e->getMessage());
    }
}

// ── Connect (the managed database may take a moment on a cold start) ───────
$conn = null;
$cfg = pg_connection_config();

for ($try = 0; $try < 20; $try++) {
    $conn = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db'], $cfg['port']);
    if ($conn) {
        break;
    }
    sleep(1);
}

if (!$conn) {
    http_response_code(503);
    header('Content-Type: text/plain');
    die("Database connection failed after 20 retries.\n"
        . "Error: " . mysqli_connect_error() . "\n\n"
        . "Check that the Render PostgreSQL database exists and that the "
        . "credentials in db.php / environment variables are correct.");
}

// Create the schema on first use against a fresh database.
pg_bootstrap_schema($conn);
