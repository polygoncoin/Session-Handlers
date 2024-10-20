<?php
include __DIR__ . '/SessionHelper.php';

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
class FileBasedSessionHandler extends SessionHelper implements \SessionHandlerInterface, \SessionIdInterface, \SessionUpdateTimestampHandlerInterface
{
    /** Spam flag */
    private $filepath = null;

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
        $this->sessionSavePath = $sessionSavePath;
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
        // only for mode files the entry of file is created
        // for other modes (DB's) only connection is established
        $filepath = $this->sessionSavePath . '/' . $sessionId;
        if (file_exists($filepath)) {
            $this->filepath = $filepath;
            $this->sessionData = $this->decryptData(file_get_contents($this->filepath));
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

        if (is_null($this->filepath)) {
            $this->filepath = $this->sessionSavePath . '/' . $sessionId;
            touch($this->filepath);
        }

        return file_put_contents($this->filepath, $this->encryptData($sessionData));
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

        if (!is_null($this->filepath) && file_exists($this->filepath)) {
            return touch($this->filepath);
        }

        return true;
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

        $datetime = date('Y-m-d H:i', ($this->currentTimestamp - $sessionMaxlifetime));
        shell_exec("find {$this->sessionSavePath} -type f -not -newermt '{$datetime}' -delete");

        return true;
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

        if (!is_null($this->filepath) && file_exists($this->filepath)) {
            return unlink($this->filepath);
        }

        $this->unsetSessionCookie();

        return true;
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

        $this->filepath = null;
        $this->currentTimestamp = null;
        $this->dataFound = false;
        $this->sessionData = null;

        return true;
    }

    /** Destructor */
    public function __destruct()
    {
        ob_end_flush(); //Flush (send) the output buffer and turn off output buffering
    }
}
