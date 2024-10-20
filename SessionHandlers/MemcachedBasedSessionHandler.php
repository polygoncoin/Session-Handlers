<?php
include __DIR__ . '/SessionHelper.php';

/**
 * Class for using Memcached based Session Handlers.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MemcachedBasedSessionHandler extends SessionHelper implements \SessionHandlerInterface, \SessionIdInterface, \SessionUpdateTimestampHandlerInterface
{
    /** DB credentials */
    public $MEMCACHED_HOSTNAME = null;
    public $MEMCACHED_PORT = null;

    /** Session max lifetime */
    public $sessionMaxlifetime = null;

    /** DB PDO object */
    private $memcacheD = null;

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
        if ($data = $this->get($sessionId)) {
            $this->sessionData = $this->decryptData($data);
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

        if (empty($this->sessionData) && empty($sessionData)) {
            return true;
        }

        return $this->set($sessionId, $this->encryptData($sessionData));
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

        return $this->delete($sessionId);
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

        return true;
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
    #[\ReturnTypeWillChange]
    public function updateTimestamp($sessionId, $sessionData)
    {
        if ($this->isSpam) {
            return true;
        }

        if (empty($this->sessionData) && empty($sessionData)) {
            return true;
        }

        return $this->set($sessionId, $this->encryptData($sessionData));
    }

    /**
     * A callable with the following signature
     *
     * @return boolean true for success or false for failure
     */
    public function close(): bool
    {
        $this->memcacheD = null;
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
            $this->memcacheD = new \Memcached();
            $this->memcacheD->addServer($this->MEMCACHED_HOSTNAME, $this->MEMCACHED_PORT);
        } catch (\Exception $e) {
            $this->manageException($e);
        }
    }

    /**
     * Get session data.
     *
     * @param string $sessionId
     * @return string
     */
    private function get($sessionId)
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

    /**
     * Set Session data.
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    private function set($sessionId, $sessionData)
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

    /**
     * Delete Session data.
     *
     * @param string $sessionId
     * @return bool
     */
    private function delete($sessionId)
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
