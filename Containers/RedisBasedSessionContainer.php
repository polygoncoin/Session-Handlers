<?php
include __DIR__ . '/SessionContainerHelper.php';

/**
 * Class for using Redis based Session Container.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class RedisBasedSessionContainer extends SessionContainerHelper
{
    public $REDIS_HOSTNAME = null;
    public $REDIS_PORT = null;
    public $REDIS_USERNAME = null;
    public $REDIS_PASSWORD = null;
    public $REDIS_DATABASE = null;

    private $redis = null;

    public function init($sessionSavePath, $sessionName)
    {
        $this->connect();
        $this->currentTimestamp = time();
    }

    public function get($sessionId)
    {
        if ($this->redis->exists($sessionId)) {
            return $this->decryptData($this->getKey($sessionId));
        }
        return false;
    }

    public function set($sessionId, $sessionData)
    {
        return $this->setKey($sessionId, $this->encryptData($sessionData));
    }

    public function touch($sessionId, $sessionData)
    {
        return $this->setKey($sessionId, $this->encryptData($sessionData));
    }

    public function gc($sessionMaxlifetime)
    {
        return true;
    }

    public function delete($sessionId)
    {
        return $this->deleteKey($sessionId);
    }

    private function connect()
    {
        try {
            $this->redis = new \Redis(
                [
                    'host' => $this->REDIS_HOSTNAME,
                    'port' => (int)$this->REDIS_PORT,
                    'connectTimeout' => 2.5,
                    'auth' => [$this->REDIS_USERNAME, $this->REDIS_PASSWORD],
                ]
            );
            $this->redis->select($this->REDIS_DATABASE);
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function getKey($sessionId)
    {
        $row = [];
        try {
            $return = false;
            if ($data = $this->redis->get($sessionId)) {
                $return = &$data;
            }
            return $return;
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function setKey($sessionId, $sessionData)
    {
        try {
            $return = false;
            if ($this->redis->set($sessionId, $sessionData, $this->sessionMaxlifetime)) {
                $return = true;
            }
            return $return;
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function deleteKey($sessionId)
    {
        try {
            $return = false;
            if ($this->redis->del($sessionId)) {
                $return = true;
            }
            return $return;
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function manageException(\Exception $e)
    {
        die($e->getMessage());
    }
}
