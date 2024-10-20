<?php
include __DIR__ . '/SessionContainerHelper.php';

/**
 * Class for using Memcached based Session Container.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MemcachedBasedSessionContainer extends SessionContainerHelper
{
    public $MEMCACHED_HOSTNAME = null;
    public $MEMCACHED_PORT = null;

    private $memcacheD = null;

    public function init($sessionSavePath, $sessionName)
    {
        $this->connect();
        $this->currentTimestamp = time();
    }

    public function get($sessionId)
    {
        if ($data = $this->getKey($sessionId)) {
            return $this->decryptData($data);
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
            $this->memcacheD = new \Memcached();
            $this->memcacheD->addServer($this->MEMCACHED_HOSTNAME, $this->MEMCACHED_PORT);
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function getKey($sessionId)
    {
        try {
            $return = false;
            if ($data = $this->memcacheD->get($sessionId)) {
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
            if ($this->memcacheD->set($sessionId, $sessionData, $this->sessionMaxlifetime)) {
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
            if ($this->memcacheD->delete($sessionId)) {
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
