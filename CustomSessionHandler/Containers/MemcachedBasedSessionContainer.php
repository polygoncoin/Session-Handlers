<?php
require_once __DIR__ . '/SessionContainerInterface.php';
require_once __DIR__ . '/SessionContainerHelper.php';

/**
 * Class for using Memcached based Session Container
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MemcachedBasedSessionContainer extends SessionContainerHelper implements SessionContainerInterface
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
        return $this->resetExpire($sessionId);
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

    private function getKey($key)
    {
        try {
            $return = false;
            if ($data = $this->memcacheD->get($key)) {
                $return = &$data;
            }
            return $return;
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function setKey($key, $value)
    {
        try {
            $return = false;
            if ($this->memcacheD->set($key, $value, $this->sessionMaxlifetime)) {
                $return = true;
            }
            return $return;
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function resetExpire($key)
    {
        try {
            $return = false;
            if ($this->memcacheD->touch($key, $this->sessionMaxlifetime)) {
                $return = true;
            }
            return $return;
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    private function deleteKey($key)
    {
        try {
            $return = false;
            if ($this->memcacheD->delete($key)) {
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
