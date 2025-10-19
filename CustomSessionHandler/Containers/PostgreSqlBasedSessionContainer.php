<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */

namespace CustomSessionHandler\Containers;

use CustomSessionHandler\Containers\SessionContainerInterface;
use CustomSessionHandler\Containers\SessionContainerHelper;

/**
 * Custom Session Handler using PostgreSql
 * php version 7
 *
 * @category  CustomSessionHandler_PgSql
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlBasedSessionContainer extends SessionContainerHelper implements
    SessionContainerInterface
{
    public $PGSQL_HOSTNAME = null;
    public $PGSQL_PORT = null;
    public $PGSQL_USERNAME = null;
    public $PGSQL_PASSWORD = null;
    public $PGSQL_DATABASE = null;
    public $PGSQL_TABLE = null;

    private $pgSqlConn = null;

    /**
     * Initialize
     *
     * @param string $sessionSavePath Session Save Path
     * @param string $sessionName     Session Name
     *
     * @return void
     */
    public function init($sessionSavePath, $sessionName): void
    {
        $this->connect();
        $this->currentTimestamp = time();
    }

    /**
     * For Custom Session Handler - Validate session ID
     *
     * @param string $sessionId Session ID
     *
     * @return bool|string
     */
    public function getSession($sessionId): bool|string
    {
        $sql = "
            SELECT session_data
            FROM {$this->PGSQL_TABLE}
            WHERE session_id = $1 AND last_accessed > $2
        ";
        $params = [
            $sessionId,
            ($this->currentTimestamp - $this->sessionMaxLifetime)
        ];

        $row = $this->getSql(sql: $sql, params: $params);
        if (isset($row['session_data'])) {
            return $this->decryptData(cipherText: $row['session_data']);
        }
        return false;
    }

    /**
     * For Custom Session Handler - Write session data
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function setSession($sessionId, $sessionData): bool|int
    {
        $sql = "
            INSERT INTO {$this->PGSQL_TABLE} (session_id, last_accessed, session_data)
            VALUES ($1, $2, $3)
        ";
        $params = [
            $sessionId,
            $this->currentTimestamp,
            $this->encryptData(plainText: $sessionData),
        ];

        return $this->execSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Update session data
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function updateSession($sessionId, $sessionData): bool|int
    {
        $sql = "
            UPDATE {$this->PGSQL_TABLE}
            SET
                last_accessed = $1,
                session_data = $2
            WHERE
                session_id = $3
        ";
        $params = [
            $this->currentTimestamp,
            $this->encryptData(plainText: $sessionData),
            $sessionId
        ];

        return $this->execSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Update session timestamp
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool
     */
    public function touchSession($sessionId, $sessionData): bool
    {
        $sql = "
            UPDATE {$this->PGSQL_TABLE}
            SET last_accessed = $1
            WHERE session_id = $2
        ";
        $params = [
            $this->currentTimestamp,
            $sessionId
        ];
        return $this->execSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Cleanup old sessions
     *
     * @param integer $sessionMaxLifetime Session Max Lifetime
     *
     * @return bool
     */
    public function gcSession($sessionMaxLifetime): bool
    {
        $sql = "
            DELETE FROM {$this->PGSQL_TABLE}
            WHERE last_accessed < $1
        ";
        $params = [
            ($this->currentTimestamp - $sessionMaxLifetime)
        ];
        return $this->execSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Destroy a session
     *
     * @param string $sessionId Session ID
     *
     * @return bool
     */
    public function deleteSession($sessionId): bool
    {
        $sql = "
            DELETE FROM {$this->PGSQL_TABLE}
            WHERE session_id = $1
        ";
        $params = [
            $sessionId
        ];
        return $this->execSql(sql: $sql, params: $params);
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function closeSession(): void
    {
        pg_close($this->pgSqlConn);
        $this->pgSqlConn = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function connect(): void
    {
        try {
            $UP = '';
            if (
                $this->PGSQL_USERNAME !== null
                && $this->PGSQL_PASSWORD !== null
            ) {
                $UP = "user={$this->PGSQL_USERNAME} password={$this->PGSQL_PASSWORD}";
            }
            $this->pgSqlConn = pg_connect(
                "host={$this->PGSQL_HOSTNAME} " .
                "port={$this->PGSQL_PORT} " .
                "dbname={$this->PGSQL_DATABASE} {$UP}"
            );
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
    }

    /**
     * Get Session
     *
     * @param string $sql    SQL
     * @param array  $params Params
     *
     * @return mixed
     */
    private function getSql($sql, $params): mixed
    {
        try {
            // Execute the query with parameters
            $result = pg_query_params($this->pgSqlConn, $sql, $params);
            if ($result) {
                $row = [];
                $rowsCount = pg_num_rows($result);
                if ($rowsCount === 1) {
                    $row = pg_fetch_assoc($result);
                }
                pg_free_result($result);
                return $row;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return false;
    }

    /**
     * Execute SQL
     *
     * @param string $sql    SQL
     * @param array  $params Params
     *
     * @return bool
     */
    private function execSql($sql, $params): bool
    {
        try {
            $result = pg_query_params($this->pgSqlConn, $sql, $params);
            if ($result) {
                return true;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return false;
    }

    /**
     * Manage Exception
     *
     * @param \Exception $e Exception
     *
     * @return never
     */
    private function manageException(\Exception $e): never
    {
        die($e->getMessage());
    }
}
