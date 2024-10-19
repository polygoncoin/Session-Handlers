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
class MySqlBasedSessionHandler extends SessionHelper implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    /** DB credentials */
    public $DB_HOSTNAME = null;
    public $DB_PORT = null;
    public $DB_USERNAME = null;
    public $DB_PASSWORD = null;
    public $DB_DATABASE = null;

    /** Session max lifetime */
    public $sessionMaxlifetime = null;

    /** DB PDO object */
    private $pdo = null;

    /** Current timestamp */
    private $currentTimestamp = null;

    /** Session data found */
    private $dataFound = false;
    
    /** Session Path */
    private $sessionSavePath = null;

    /** Session Name */
    private $sessionName = null;

    /** Session Data */
    private $sessionData = '';

    /** Spam flag */
    private $isSpam = false;

    /**
     * A callable with the following signature
     *
     * @param string $savePath
     * @param string $sessionName
     * @return boolean true for success or false for failure
     */
    public function open($sessionSavePath, $sessionName): bool
    {
        $this->sessionSavePath = $sessionSavePath;
        $this->sessionName = $sessionName;

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
    #[\ReturnTypeWillChange]
    public function validateId($sessionId)
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
        if ($this->isSpam) {
            setcookie($this->sessionName,'',1);
        }

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
    #[\ReturnTypeWillChange]
    public function read($sessionId): string
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
        if ($this->sessionData === $sessionData || empty($sessionData)) {
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

        $return = false;
        if ($this->set($sql, $params)) {
            $return = true;
        }

        return $return;
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
        $sql = 'DELETE FROM `sessions` WHERE `sessionId` = :sessionId';
        $params = [
            ':sessionId' => $sessionId
        ];

        $return = false;
        if ($this->set($sql, $params)) {
            $return = true;
        }

        return $return;
    }

    /**
     * A callable with the following signature
     *
     * @param integer $sessionMaxlifetime
     * @return boolean true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function gc($sessionMaxlifetime): bool
    {
        if ($this->isSpam) {
            return true;
        }
        $lastAccessed = $this->currentTimestamp - $sessionMaxlifetime;
        $sql = 'DELETE FROM `sessions` WHERE `lastAccessed` < :lastAccessed';
        $params = [
            ':lastAccessed' => $lastAccessed
        ];

        $return = false;
        if ($this->set($sql, $params)) {
            $return = true;
        }

        return $return;
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function updateTimestamp($sessionId, $sessionData)
    {
        if ($this->isSpam) {
            return true;
        }
        $sql = 'UPDATE `sessions` SET `lastAccessed` = :lastAccessed WHERE `sessionId` = :sessionId';
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => $this->currentTimestamp
        ];
        $return = false;
        if ($this->set($sql, $params)) {
            $return = true;
        }

        return $return;
    }

    /**
     * A callable with the following signature
     *
     * @return boolean true for success or false for failure
     */
    public function close(): bool
    {
        if ($this->isSpam) {
            return true;
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
            $affectedRows = $stmt->rowCount();
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->manageException($e);
        }
        return $affectedRows;
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
}
