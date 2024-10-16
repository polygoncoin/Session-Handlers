<?php
/**
 * Class for using File based Session Handlers.
 * 
 * @category   Session
 * @package    File based Session Handler
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class FileBasedSessionHandler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    /** Session max lifetime */
    public $sessionMaxlifetime = null;

    /** File handle */
    private $handle = null;

    /** Current timestamp */
    private $currentTimestamp = null;

    /** Session data found */
    private $dataFound = false;
    
    /** Session Path */
    private $sessionSavePath = null;

    /** Session Name */
    private $sessionName = null;

    /** Session Id */
    private $sessionId = null;

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
    function open($sessionSavePath, $sessionName): bool
    {
        $this->sessionSavePath = $sessionSavePath;
        $this->sessionName = $sessionName;

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
        $this->sessionId = $sessionId;

        // only for mode files the entry of file is created
        // for other modes (DB's) only connection is established
        $filepath = $this->sessionSavePath . '/' . $this->sessionId;
        if (file_exists($filepath)) {
            $this->handle = fopen($filepath, 'rw+b');
            $this->sessionData = fread($this->handle, 4096);
            $this->dataFound = true;
            // flock($this->handle, LOCK_EX); // locks file handle
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
        return uniqid('', true);
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return string the session data or an empty string
     */
    #[\ReturnTypeWillChange]
    function read($sessionId)
    {
        if ($this->isSpam) {
            return '';
        }
        if ($this->handle) {
            return fread($this->handle, 4096);
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
    function write($sessionId, $sessionData): bool
    {
        if ($this->isSpam) {
            return true;
        }
        if ($this->sessionData === $sessionData || empty($sessionData)) {
            return true;
        }

        if (!$this->handle) {
            $this->handle = fopen($this->sessionSavePath . '/' . $sessionId, 'w+b');
        }
        fwrite($this->handle, $sessionData);
        return true;
    }

    /**
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return boolean true for success or false for failure
     */
    function destroy($sessionId): bool
    {
        if ($this->isSpam) {
            return true;
        }
        if ($this->handle) {
            fclose($this->handle);
        }
        unlink($this->sessionSavePath . '/' . $sessionId);

        return true;
    }

    /**
     * A callable with the following signature
     *
     * @param integer $sessionMaxlifetime
     * @return boolean true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    function gc($sessionMaxlifetime)
    {
        if ($this->isSpam) {
            return true;
        }
        $datetime = date('Y-m-d H:i', ($this->currentTimestamp - $sessionMaxlifetime));
        shell_exec("find {$this->sessionSavePath} -type f -not -newermt '{$datetime}' -delete");
        return true;
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
        return touch($this->sessionSavePath . '/' . $sessionId);
    }

    /**
     * A callable with the following signature
     *
     * @return boolean true for success or false for failure
     */
    function close(): bool
    {
        if ($this->isSpam) {
            return true;
        }
        if ($this->handle) {
            return fclose($this->handle);
        }
        return true;
    }
}
