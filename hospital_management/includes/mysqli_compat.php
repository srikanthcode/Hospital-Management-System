<?php
/**
 * mysqli API compatibility layer over PDO + PostgreSQL.
 *
 * The application is written against the procedural mysqli_* API, which only
 * speaks the MySQL/MariaDB wire protocol. This shim re-implements the subset of
 * that API the app actually uses on top of PDO's pgsql driver, so the existing
 * pages run unchanged against a PostgreSQL database.
 *
 * Loaded by db.php. Requires the pdo_pgsql extension and that the mysqli
 * extension is NOT compiled in (otherwise the native functions win).
 */

if (!defined('MYSQLI_REPORT_OFF')) define('MYSQLI_REPORT_OFF', 0);
if (!defined('MYSQLI_REPORT_ERROR')) define('MYSQLI_REPORT_ERROR', 1);
if (!defined('MYSQLI_REPORT_STRICT')) define('MYSQLI_REPORT_STRICT', 2);
if (!defined('MYSQLI_ASSOC')) define('MYSQLI_ASSOC', 1);
if (!defined('MYSQLI_NUM')) define('MYSQLI_NUM', 2);
if (!defined('MYSQLI_BOTH')) define('MYSQLI_BOTH', 3);

$GLOBALS['__pg_last_connect_error'] = null;

class PgConnection
{
    /** @var PDO */
    public $pdo;
    public $insertId = 0;
    public $lastError = '';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}

class PgResult
{
    private $rows;
    private $cursor = 0;
    public $affected = 0;

    public function __construct(array $rows, $affected = 0)
    {
        $this->rows = $rows;
        $this->affected = (int)$affected;
    }

    public function fetchAssocRow()
    {
        if ($this->cursor >= count($this->rows)) {
            return null;
        }
        return $this->rows[$this->cursor++];
    }

    public function fetchNumRow()
    {
        $row = $this->fetchAssocRow();
        return $row === null ? null : array_values($row);
    }

    public function count()
    {
        return count($this->rows);
    }
}

class PgStmt
{
    /** @var PgConnection */
    public $conn;
    public $sql = '';
    public $types = '';
    public $values = [];
    /** @var PDOStatement */
    public $st = null;
    // True when "RETURNING id" was appended so execute() can read the new id.
    public $appendReturning = false;

    public function bindParams($types, array $values)
    {
        $this->types = (string)$types;
        $this->values = $values;
    }

    /**
     * Cast bound values to their declared SQL types. PostgreSQL is much stricter
     * about parameter types than MySQL, so an 'i' must arrive as an integer and
     * a null must stay null (never the empty string).
     */
    public function typedParams()
    {
        $out = [];
        $len = strlen($this->types);
        foreach ($this->values as $i => $v) {
            $t = ($i < $len) ? $this->types[$i] : 's';
            if ($v === null) {
                $out[] = null;
            } elseif ($t === 'i') {
                $out[] = (int)$v;
            } elseif ($t === 'd') {
                $out[] = (float)$v;
            } else {
                $out[] = (string)$v;
            }
        }
        return $out;
    }
}

/** Last sequence value used by this session (mysqli_insert_id equivalent). */
function pg_compat_lastval(PgConnection $conn)
{
    try {
        $row = $conn->pdo->query('SELECT lastval() AS v')->fetch(PDO::FETCH_ASSOC);
        return ($row && $row['v'] !== null && $row['v'] !== false) ? (int)$row['v'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

if (!function_exists('mysqli_connect')) {
    function mysqli_connect($host, $user = '', $pass = '', $db = '', $port = 5432, $socket = null)
    {
        $GLOBALS['__pg_last_connect_error'] = null;
        $sslmode = (string)(getenv('PGSSLMODE') !== false ? getenv('PGSSLMODE') : 'require');
        $dsn = 'pgsql:host=' . $host . ';port=' . (int)$port . ';dbname=' . $db . ';sslmode=' . $sslmode;
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return new PgConnection($pdo);
        } catch (PDOException $e) {
            $GLOBALS['__pg_last_connect_error'] = $e->getMessage();
            return false;
        }
    }
}

if (!function_exists('mysqli_connect_error')) {
    function mysqli_connect_error()
    {
        return isset($GLOBALS['__pg_last_connect_error']) ? (string)$GLOBALS['__pg_last_connect_error'] : '';
    }
}

if (!function_exists('mysqli_set_charset')) {
    function mysqli_set_charset($conn, $charset)
    {
        // Connection encoding is negotiated as UTF8 by the pgsql driver.
        return true;
    }
}

if (!function_exists('mysqli_error')) {
    function mysqli_error($conn)
    {
        return ($conn instanceof PgConnection) ? $conn->lastError : '';
    }
}

if (!function_exists('mysqli_real_escape_string')) {
    function mysqli_real_escape_string($conn, $s)
    {
        if (!($conn instanceof PgConnection)) {
            return addslashes((string)$s);
        }
        if ($s === null) {
            return '';
        }
        // PDO::quote() escapes for a PostgreSQL string literal; strip the
        // surrounding quotes it adds so callers can embed the value themselves.
        $quoted = $conn->pdo->quote((string)$s);
        return substr($quoted, 1, -1);
    }
}

/**
 * True when the statement writes rows whose generated id we may need via
 * mysqli_insert_id(). Appending RETURNING id makes that value deterministic
 * (lastval() can return a stale value after an ON CONFLICT update path).
 */
function pg_is_insert($sql)
{
    return (bool)preg_match('/^\s*INSERT\b/i', (string)$sql);
}

/** True when the SQL already has a RETURNING clause. */
function pg_has_returning($sql)
{
    return (bool)preg_match('/\bRETURNING\b/i', (string)$sql);
}

if (!function_exists('mysqli_query')) {
    function mysqli_query($conn, $sql)
    {
        $sql = trim((string)$sql);
        try {
            if (preg_match('/^\s*(SELECT|SHOW|EXPLAIN|WITH|VALUES)\b/i', $sql)) {
                $st = $conn->pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll(PDO::FETCH_ASSOC);
                $result = new PgResult($rows, count($rows));
            } elseif (pg_is_insert($sql) && !pg_has_returning($sql)) {
                $st = $conn->pdo->prepare($sql . ' RETURNING id');
                $st->execute();
                $row = $st->fetch(PDO::FETCH_ASSOC);
                $result = new PgResult([], $st->rowCount());
                if ($row && isset($row['id'])) {
                    $conn->insertId = (int)$row['id'];
                }
            } else {
                $affected = $conn->pdo->exec($sql);
                $result = new PgResult([], $affected);
                if (pg_is_insert($sql)) {
                    $conn->insertId = pg_compat_lastval($conn);
                }
            }
            return $result;
        } catch (PDOException $e) {
            if ($conn instanceof PgConnection) {
                $conn->lastError = $e->getMessage();
            }
            throw $e;
        }
    }
}

if (!function_exists('mysqli_prepare')) {
    function mysqli_prepare($conn, $sql)
    {
        $stmt = new PgStmt();
        $stmt->conn = $conn;
        $stmt->sql = trim((string)$sql);
        $stmt->appendReturning = (pg_is_insert($stmt->sql) && !pg_has_returning($stmt->sql));
        try {
            $stmt->st = $conn->pdo->prepare($stmt->appendReturning ? $stmt->sql . ' RETURNING id' : $stmt->sql);
        } catch (PDOException $e) {
            if ($conn instanceof PgConnection) {
                $conn->lastError = $e->getMessage();
            }
            throw $e;
        }
        return $stmt;
    }
}

if (!function_exists('mysqli_stmt_bind_param')) {
    function mysqli_stmt_bind_param($stmt, $types, ...$vars)
    {
        $stmt->bindParams($types, $vars);
        return true;
    }
}

if (!function_exists('mysqli_stmt_execute')) {
    function mysqli_stmt_execute($stmt)
    {
        try {
            $stmt->st->execute($stmt->typedParams());
            if ($stmt->appendReturning) {
                $row = $stmt->st->fetch(PDO::FETCH_ASSOC);
                $stmt->conn->insertId = ($row && isset($row['id'])) ? (int)$row['id'] : 0;
            } elseif (pg_is_insert($stmt->sql)) {
                $stmt->conn->insertId = pg_compat_lastval($stmt->conn);
            }
            return true;
        } catch (PDOException $e) {
            if ($stmt->conn instanceof PgConnection) {
                $stmt->conn->lastError = $e->getMessage();
            }
            throw $e;
        }
    }
}

if (!function_exists('mysqli_stmt_get_result')) {
    function mysqli_stmt_get_result($stmt)
    {
        $rows = $stmt->st->fetchAll(PDO::FETCH_ASSOC);
        return new PgResult($rows, count($rows));
    }
}

if (!function_exists('mysqli_stmt_affected_rows')) {
    function mysqli_stmt_affected_rows($stmt)
    {
        return $stmt->st->rowCount();
    }
}

if (!function_exists('mysqli_fetch_assoc')) {
    function mysqli_fetch_assoc($result)
    {
        if (!($result instanceof PgResult)) {
            return null;
        }
        return $result->fetchAssocRow();
    }
}

if (!function_exists('mysqli_fetch_all')) {
    function mysqli_fetch_all($result, $result_type = MYSQLI_NUM)
    {
        if (!($result instanceof PgResult)) {
            return [];
        }
        $out = [];
        $assoc = ($result_type === MYSQLI_ASSOC || $result_type === MYSQLI_BOTH);
        while (($row = $result->fetchAssocRow()) !== null) {
            $out[] = $assoc ? $row : array_values($row);
        }
        return $out;
    }
}

if (!function_exists('mysqli_num_rows')) {
    function mysqli_num_rows($result)
    {
        if (!($result instanceof PgResult)) {
            return 0;
        }
        return $result->count();
    }
}

if (!function_exists('mysqli_insert_id')) {
    function mysqli_insert_id($conn)
    {
        return ($conn instanceof PgConnection) ? (int)$conn->insertId : 0;
    }
}

if (!function_exists('mysqli_close')) {
    function mysqli_close($conn)
    {
        if ($conn instanceof PgConnection) {
            $conn->pdo = null;
        }
        return true;
    }
}

if (!function_exists('mysqli_report')) {
    function mysqli_report($mode)
    {
        // PDO always throws on error (like PHP 8.1+ mysqli default report mode).
        return true;
    }
}

if (!function_exists('mysqli_data_seek')) {
    function mysqli_data_seek($result, $offset)
    {
        // Not used by the app; provided for completeness.
        return true;
    }
}
