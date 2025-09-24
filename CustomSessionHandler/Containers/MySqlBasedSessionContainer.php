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
class MySqlBasedSessionContainer extends SessionContainerHelper
    implements SessionContainerInterface
{
    public $DB_HOSTNAME = null;
    public $DB_PORT = null;
    public $DB_USERNAME = null;
    public $DB_PASSWORD = null;
    public $DB_DATABASE = null;
    public $DB_TABLE = null;

    private $_pdo = null;

    private $_foundSession = false;

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
        $this->_connect();
        $this->currentTimestamp = time();
    }

    /**
     * For Custom Session Handler - Validate session ID
     *
     * @param string $sessionId Session ID
     *
     * @return bool|string
     */
    public function get($sessionId): bool|string
    {
        $this->_foundSession = false;
        $sql = "
            SELECT `sessionData`
            FROM `{$this->DB_DATABASE}`.`{$this->DB_TABLE}`
            WHERE `sessionId` = :sessionId AND lastAccessed > :lastAccessed
        ";
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => ($this->currentTimestamp - $this->sessionMaxLifetime)
        ];
        if (($row = $this->_getSql(sql: $sql, params: $params))
            && isset($row['sessionData'])
        ) {
            $this->_foundSession = true;
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
    public function set($sessionId, $sessionData): bool|int
    {
        if ($this->_foundSession) {
            $sql = "
                UPDATE `{$this->DB_DATABASE}`.`{$this->DB_TABLE}`
                SET
                    `sessionData` = :sessionData,
                    `lastAccessed` = :lastAccessed
                WHERE
                    `sessionId` = :sessionId
            ";
        } else {
            $sql = "
                INSERT INTO `{$this->DB_DATABASE}`.`{$this->DB_TABLE}`
                SET
                    `sessionData` = :sessionData,
                    `lastAccessed` = :lastAccessed,
                    `sessionId` = :sessionId
            ";
        }
        $params = [
            ':sessionId' => $sessionId,
            ':sessionData' => $this->encryptData(plainText: $sessionData),
            ':lastAccessed' => $this->currentTimestamp
        ];

        return $this->_setSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Update session timestamp
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool
     */
    public function touch($sessionId, $sessionData): bool
    {
        $sql = "
            UPDATE `{$this->DB_DATABASE}`.`{$this->DB_TABLE}`
            SET `lastAccessed` = :lastAccessed
            WHERE `sessionId` = :sessionId
        ";
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => $this->currentTimestamp
        ];
        return $this->_setSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Cleanup old sessions
     *
     * @param integer $sessionMaxLifetime Session Max Lifetime
     *
     * @return bool
     */
    public function gc($sessionMaxLifetime): bool
    {
        $lastAccessed = $this->currentTimestamp - $sessionMaxLifetime;
        $sql = "
            DELETE FROM `{$this->DB_DATABASE}`.`{$this->DB_TABLE}`
            WHERE `lastAccessed` < :lastAccessed
        ";
        $params = [
            ':lastAccessed' => $lastAccessed
        ];
        return $this->_setSql(sql: $sql, params: $params);
    }

    /**
     * For Custom Session Handler - Destroy a session
     *
     * @param string $sessionId Session ID
     *
     * @return bool
     */
    public function delete($sessionId): bool
    {
        $sql = "
            DELETE FROM `{$this->DB_DATABASE}`.`{$this->DB_TABLE}`
            WHERE `sessionId` = :sessionId
        ";
        $params = [
            ':sessionId' => $sessionId
        ];
        return $this->_setSql(sql: $sql, params: $params);
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function close(): void
    {
        $this->_pdo = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function _connect(): void
    {
        try {
            $this->_pdo = new \PDO(
                dsn: "mysql:host={$this->DB_HOSTNAME}",
                username: $this->DB_USERNAME,
                password: $this->DB_PASSWORD,
                options: [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
        }
    }

    /**
     * Get SQL
     *
     * @param string $sql    SQL
     * @param array  $params Params
     *
     * @return mixed
     */
    private function _getSql($sql, $params = []): mixed
    {
        $row = [];
        try {
            $stmt = $this->_pdo->prepare(
                query: $sql,
                options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            $stmt->execute(params: $params);
            switch($stmt->rowCount()) {
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
            $this->_manageException(e: $e);
        }
        return $row;
    }

    /**
     * Set SQL
     *
     * @param string $sql    SQL
     * @param array  $params Params
     *
     * @return bool
     */
    private function _setSql($sql, $params = []): bool
    {
        try {
            $stmt = $this->_pdo->prepare(
                query: $sql,
                options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            $stmt->execute(params: $params);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
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
    private function _manageException(\Exception $e): never
    {
        die($e->getMessage());
    }
}
