<?php
include __DIR__ . '/SessionHelper.php';

/**
 * Class for using MySql based Session Handlers.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MySqlBasedSessionHandler extends SessionHelper implements \SessionHandlerInterface, \SessionIdInterface, \SessionUpdateTimestampHandlerInterface
{
    /** DB credentials */
    public $DB_HOSTNAME = null;
    public $DB_PORT = null;
    public $DB_USERNAME = null;
    public $DB_PASSWORD = null;
    public $DB_DATABASE = null;

    /** DB PDO object */
    private $pdo = null;

    /** Constructor */
    public function __construct()
    {
        ob_start(); // Turn on output buffering
    }

    /**
     * A callable with the following signature
     *
     * @param string $savePath
     * @param string $sessionName
     * @return boolean true for success or false for failure
     */
    public function open($sessionSavePath, $sessionName): bool
    {

        $this->connect();
        $this->currentTimestamp = time();

        return true;
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return string true if the session id is valid otherwise false
     */
    public function validateId($sessionId): bool
    {
        $sql = 'SELECT `sessionData` FROM `sessions` WHERE `sessionId` = :sessionId';
        $params = [
            ':sessionId' => $sessionId
        ];
        $row = $this->get($sql, $params);

        if (isset($row['sessionData'])) {
            $this->sessionData = $this->decryptData($row['sessionData']);
            $this->dataFound = true;
        }

        /** marking spam request */
        $this->isSpam = !$this->dataFound;

        return true;
    }

    /**
     * A callable with the following signature
     * Invoked internally when a new session id is needed
     *
     * @return string should be new session id
     */
    public function create_sid(): string
    {
        if ($this->isSpam) {
            return '';
        }

        return $this->getRandomString();
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return string the session data or an empty string
     */
    public function read($sessionId): string|false
    {
        if ($this->isSpam) {
            return '';
        }

        if (!empty($this->sessionData)) {
            return $this->sessionData;
        }

        return '';
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean true for success or false for failure
     */
    public function write($sessionId, $sessionData): bool
    {
        if ($this->isSpam) {
            return true;
        }

        if (empty($this->sessionData) && empty($sessionData)) {
            return true;
        }

        // $sessionData can be applied unserialize() function to manipulate data.
        // Like if userId is preferred to be stored in another column this can be done.
        // Respective changes needs to be done in session table structure as well.

        if ($this->dataFound) {
            $sql = 'UPDATE `sessions` SET `sessionData` = :sessionData, `lastAccessed` = :lastAccessed WHERE `sessionId` = :sessionId';
        } else {
            $sql = 'INSERT INTO `sessions` SET `sessionData` = :sessionData, `lastAccessed` = :lastAccessed, `sessionId` = :sessionId';
        }
        $params = [
            ':sessionId' => $sessionId,
            ':sessionData' => $this->encryptData($sessionData),
            ':lastAccessed' => $this->currentTimestamp
        ];

        return $this->set($sql, $params);
    }

    /**
     * A callable with the following signature
     * When session.lazy_write is enabled, and session data is unchanged
     * UpdateTimestamp is called instead (of write) to only update the timestamp of session.
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean true for success or false for failure
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        if ($this->isSpam) {
            return true;
        }

        if (empty($this->sessionData) && empty($sessionData)) {
            return true;
        }

        $sql = 'UPDATE `sessions` SET `lastAccessed` = :lastAccessed WHERE `sessionId` = :sessionId';
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => $this->currentTimestamp
        ];

        return $this->set($sql, $params);
    }

    /**
     * A callable with the following signature
     *
     * @param integer $sessionMaxlifetime
     * @return boolean true for success or false for failure
     */
    public function gc($sessionMaxlifetime): int|false
    {
        if ($this->isSpam) {
            return true;
        }

        $lastAccessed = $this->currentTimestamp - $sessionMaxlifetime;
        $sql = 'DELETE FROM `sessions` WHERE `lastAccessed` < :lastAccessed';
        $params = [
            ':lastAccessed' => $lastAccessed
        ];

        return $this->set($sql, $params);
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return boolean true for success or false for failure
     */
    public function destroy($sessionId): bool
    {
        if ($this->isSpam) {
            return true;
        }

        $this->unsetSessionCookie();

        $sql = 'DELETE FROM `sessions` WHERE `sessionId` = :sessionId';
        $params = [
            ':sessionId' => $sessionId
        ];

        return $this->set($sql, $params);
    }

    /**
     * A callable with the following signature
     *
     * @return boolean true for success or false for failure
     */
    public function close(): bool
    {
        if ($this->isSpam) {
            $this->unsetSessionCookie();
        }

        $this->pdo = null;
        $this->currentTimestamp = null;
        $this->dataFound = false;
        $this->sessionData = null;

        return true;
    }

    /**
     * Set PDO connection
     *
     * @return void
     */
    private function connect()
    {
        try {
            $this->pdo = new \PDO(
                "mysql:host={$this->DB_HOSTNAME};dbname={$this->DB_DATABASE}",
                $this->DB_USERNAME,
                $this->DB_PASSWORD,
                [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );    
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    /**
     * Get SQL data.
     *
     * @param string $sql
     * @param string $params
     * @return array
     */
    private function get($sql, $params = [])
    {
        $row = [];
        try {
            $stmt = $this->pdo->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $stmt->execute($params);
            switch($stmt->rowCount()) {
                case 0:
                    $row = [];
                    break;
                case 1:
                    $row = $stmt->fetch();
                    break;
                default:
                    // $this->destroy($params['sessionId']);
                    $row = false;
                    break;
            }
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->manageException($e);
        }
        return $row;
    }

    /**
     * Set SQL data.
     *
     * @param string $sql
     * @param string $params
     * @return integer Affected rows
     */
    private function set($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $stmt->execute($params);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->manageException($e);
        }
        return true;
    }

    /**
     * Handle Exception
     *
     * @param object $e
     * @return void
     */
    private function manageException(\Exception $e)
    {
        die($e->getMessage());
    }

    /** Destructor */
    public function __destruct()
    {
        ob_end_flush(); //Flush (send) the output buffer and turn off output buffering
    }
}
