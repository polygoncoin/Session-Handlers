<?php
require_once __DIR__ . '/SessionContainerInterface.php';
require_once __DIR__ . '/SessionContainerHelper.php';

/**
 * Class for using MySql based Session Container
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MySqlBasedSessionContainer extends SessionContainerHelper implements SessionContainerInterface
{
    public $DB_HOSTNAME = null;
    public $DB_PORT = null;
    public $DB_USERNAME = null;
    public $DB_PASSWORD = null;
    public $DB_DATABASE = null;
    public $DB_TABLE = null;

    private $pdo = null;

    private $foundSession = false;

    public function init($sessionSavePath, $sessionName)
    {
        $this->connect();
        $this->currentTimestamp = time();
    }

    public function get($sessionId)
    {
        $this->foundSession = false;
        $sql = "SELECT `sessionData` FROM `{$this->DB_DATABASE}`.`{$this->DB_TABLE}` WHERE `sessionId` = :sessionId AND lastAccessed > :lastAccessed";
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => ($this->currentTimestamp - $this->sessionMaxlifetime)
        ];
        if (($row = $this->getSql($sql, $params)) && isset($row['sessionData'])) {
            $this->foundSession = true;
            return $this->decryptData($row['sessionData']);
        }
        return false;
    }

    public function set($sessionId, $sessionData)
    {
        if ($this->foundSession) {
            $sql = "UPDATE `{$this->DB_DATABASE}`.`{$this->DB_TABLE}` SET `sessionData` = :sessionData, `lastAccessed` = :lastAccessed WHERE `sessionId` = :sessionId";
        } else {
            $sql = "INSERT INTO `{$this->DB_DATABASE}`.`{$this->DB_TABLE}` SET `sessionData` = :sessionData, `lastAccessed` = :lastAccessed, `sessionId` = :sessionId";
        }
        $params = [
            ':sessionId' => $sessionId,
            ':sessionData' => $this->encryptData($sessionData),
            ':lastAccessed' => $this->currentTimestamp
        ];

        return $this->setSql($sql, $params);
    }

    public function touch($sessionId, $sessionData)
    {
        $sql = "UPDATE `{$this->DB_DATABASE}`.`{$this->DB_TABLE}` SET `lastAccessed` = :lastAccessed WHERE `sessionId` = :sessionId";
        $params = [
            ':sessionId' => $sessionId,
            ':lastAccessed' => $this->currentTimestamp
        ];
        return $this->setSql($sql, $params);
    }

    public function gc($sessionMaxlifetime)
    {
        $lastAccessed = $this->currentTimestamp - $sessionMaxlifetime;
        $sql = "DELETE FROM `{$this->DB_DATABASE}`.`{$this->DB_TABLE}` WHERE `lastAccessed` < :lastAccessed";
        $params = [
            ':lastAccessed' => $lastAccessed
        ];
        return $this->setSql($sql, $params);
    }

    public function delete($sessionId)
    {
        $sql = "DELETE FROM `{$this->DB_DATABASE}`.`{$this->DB_TABLE}` WHERE `sessionId` = :sessionId";
        $params = [
            ':sessionId' => $sessionId
        ];
        return $this->setSql($sql, $params);
    }

    private function connect()
    {
        try {
            $this->pdo = new \PDO(
                "mysql:host={$this->DB_HOSTNAME}",
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

    private function getSql($sql, $params = [])
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
                    $row = false;
                    break;
            }
            $stmt->closeCursor();
        } catch (\Exception $e) {
            $this->manageException($e);
        }
        return $row;
    }

    private function setSql($sql, $params = [])
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

    private function manageException(\Exception $e)
    {
        die($e->getMessage());
    }
}
