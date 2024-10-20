<?php
/**
 * Class for using File based Session Handlers.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class CustomSessionHandler implements \SessionHandlerInterface, \SessionIdInterface, \SessionUpdateTimestampHandlerInterface
{
    /** Spam flag */
    public $container = null;

    /** Session cookie name */
    public $sessionName = null;

    /** Session data cookie name */
    public $sessionDataName = null;

    /** Session Data */
    public $sessionData = '';

    /** Session data found */
    public $dataFound = false;

    /** Spam flag */
    public $isSpam = false;

    /** Constructor */
    public function __construct(&$container)
    {
        ob_start(); // Turn on output buffering
        $this->container = &$container;
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
        $this->container->init($sessionSavePath, $sessionName);

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
        if ($sessionData = $this->container->get($sessionId)) {
            $this->sessionData = &$sessionData;
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

        return $this->container->set($sessionId, $sessionData);
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

        return $this->container->touch($sessionId, $sessionData);
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

        return $this->container->gc($sessionMaxlifetime);
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

        return $this->container->delete($sessionId);
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

        $this->sessionData = null;
        $this->dataFound = false;
        $this->isSpam = false;

        return true;
    }

    /**
     * Returns random 64 char string
     *
     * @return string
     */
    private function getRandomString()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Unset session cookies
     *
     * @return void
     */
    protected function unsetSessionCookie()
    {
        if (!empty($this->sessionName)) {
            setcookie($this->sessionName, '', 1);
            setcookie($this->sessionName, '', 1, '/');    
        }
        if (!empty($this->sessionDataName) || isset($_COOKIE[$this->sessionDataName])) {
            setcookie($this->sessionDataName,'',1);
            setcookie($this->sessionDataName,'',1, '/');
        }
    }

    /** Destructor */
    public function __destruct()
    {
        $this->container = null;
        ob_end_flush(); //Flush (send) the output buffer and turn off output buffering
    }
}
