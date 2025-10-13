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
 * Custom Session Handler using MySQL
 * php version 7
 *
 * @category  CustomSessionHandler_MySQL
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class MySqlBasedSessionContainer extends SessionContainerHelper implements
    SessionContainerInterface
{
    public $MYSQL_HOSTNAME = null;
    public $MYSQL_PORT = null;
    public $MYSQL_USERNAME = null;
    public $MYSQL_PASSWORD = null;
    public $MYSQL_DATABASE = null;
    public $MYSQL_TABLE = null;

    private $pdo = null;

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
            SELECT `sessionData`
            FROM `{$this->MYSQL_DATABASE}`.`{$this->MYSQL_TABLE}`
            WHERE `sessionId` = :sessionId AND lastAccessed > :lastAccessed
        ";
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => ($this->currentTimestamp - $this->sessionMaxLifetime)
        ];
        if (
            ($row = $this->getSql(sql: $sql, params: $params))
            && isset($row['sessionData'])
        ) {
            return $this->decryptData(cipherText: $row['sessionData']);
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
            INSERT INTO `{$this->MYSQL_DATABASE}`.`{$this->MYSQL_TABLE}`
            SET
                `sessionData` = :sessionData,
                `lastAccessed` = :lastAccessed,
                `sessionId` = :sessionId
        ";
        $params = [
            ':sessionId' => $sessionId,
            ':sessionData' => $this->encryptData(plainText: $sessionData),
            ':lastAccessed' => $this->currentTimestamp
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
            UPDATE `{$this->MYSQL_DATABASE}`.`{$this->MYSQL_TABLE}`
            SET
                `sessionData` = :sessionData,
                `lastAccessed` = :lastAccessed
            WHERE
                `sessionId` = :sessionId
        ";
        $params = [
            ':sessionId' => $sessionId,
            ':sessionData' => $this->encryptData(plainText: $sessionData),
            ':lastAccessed' => $this->currentTimestamp
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
            UPDATE `{$this->MYSQL_DATABASE}`.`{$this->MYSQL_TABLE}`
            SET `lastAccessed` = :lastAccessed
            WHERE `sessionId` = :sessionId
        ";
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => $this->currentTimestamp
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
        $lastAccessed = $this->currentTimestamp - $sessionMaxLifetime;
        $sql = "
            DELETE FROM `{$this->MYSQL_DATABASE}`.`{$this->MYSQL_TABLE}`
            WHERE `lastAccessed` < :lastAccessed
        ";
        $params = [
            ':lastAccessed' => $lastAccessed
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
            DELETE FROM `{$this->MYSQL_DATABASE}`.`{$this->MYSQL_TABLE}`
            WHERE `sessionId` = :sessionId
        ";
        $params = [
            ':sessionId' => $sessionId
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
        $this->pdo = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function connect(): void
    {
        try {
            $this->pdo = new \PDO(
                dsn: "mysql:host={$this->MYSQL_HOSTNAME}",
                username: $this->MYSQL_USERNAME,
                password: $this->MYSQL_PASSWORD,
                options: [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
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
    private function getSql($sql, $params = []): mixed
    {
        $row = [];
        try {
            $stmt = $this->pdo->prepare(
                query: $sql,
                options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            $stmt->execute(params: $params);
            switch ($stmt->rowCount()) {
                case 0:
                    $row = [];
                    break;
                case 1:
                    $row = $stmt->fetch();
                    break;
                default:
                    $row = false;
                    break;
            }
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return $row;
    }

    /**
     * Execute SQL
     *
     * @param string $sql    SQL
     * @param array  $params Params
     *
     * @return bool
     */
    private function execSql($sql, $params = []): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                query: $sql,
                options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            $stmt->execute(params: $params);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return true;
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
