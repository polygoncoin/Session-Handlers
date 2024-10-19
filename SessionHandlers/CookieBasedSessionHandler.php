<?php
include __DIR__ . '/SessionHelper.php';

/**
 * Class for using Cookie based Session Handlers.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class CookieBasedSessionHandler extends SessionHelper implements \SessionHandlerInterface, \SessionIdInterface, \SessionUpdateTimestampHandlerInterface
{
    /** Session max lifetime */
    public $sessionMaxlifetime = null;

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

    /** Session data cookie name */
    private $sessionDataName = 'PHPSESSDATA';

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
    function open($sessionSavePath, $sessionName): bool
    {
        if (empty($this->passphrase) || empty($this->iv)) {
            die ('Please set encryption details in Session.php');
        }

        $this->sessionSavePath = $sessionSavePath;
        $this->sessionName = $sessionName;
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
        if (isset($_COOKIE[$this->sessionDataName]) && !empty($_COOKIE[$this->sessionDataName])) {
            $sessionData = $this->decryptData($_COOKIE[$this->sessionDataName]);
            $sessionDataArr = unserialize($sessionData);
            if (
                isset($sessionDataArr['_TS_']) &&
                ($sessionDataArr['_TS_'] + $this->sessionMaxlifetime) > $this->currentTimestamp
            ) {
                $this->sessionData = $sessionData;
                $this->dataFound = true;
            }
        }

        /** marking spam request */
        $this->isSpam = !$this->dataFound;
        if ($this->isSpam) {
            setcookie($this->sessionDataName,'',1);
        }

        return true;
    }

    /**
     * A callable with the following signature
     * Invoked internally when a new session id is needed
     *
     * @return string should be new session id
     */
    public function create_sid()
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
    public function read($sessionId)
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
        
        $sessionDataArr = unserialize($sessionData);
        $sessionDataArr['_TS_'] = $this->currentTimestamp;
        $sessionData = serialize($sessionDataArr);

        $cookieData = $this->encryptData($sessionData);
        if (strlen($cookieData) > 4096) {
            ob_end_clean();
            die('Session data length exceeds max 4 kilobytes (KB) supported per Cookie');
        }

        return setcookie(
            $name = $this->sessionDataName,
            $value = $cookieData,
            $expires = 0,
            $path = '/',
            $domain = '',
            $secure = false,
            $httponly = true
        );
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

        setcookie($this->sessionName, '', 1);
        setcookie($this->sessionDataName, '', 1);

        return true;
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

        $sessionDataArr = unserialize($sessionData);
        $sessionDataArr['_TS_'] = $this->currentTimestamp;
        $sessionData = serialize($sessionDataArr);

        $cookieData = $this->encryptData($sessionData);
        if (strlen($cookieData) > 4096) {
            ob_end_clean();
            die('Session data length exceeds max 4 kilobytes (KB) supported per Cookie');
        }

        return setcookie(
            $name = $this->sessionDataName,
            $value = $cookieData,
            $expires = 0,
            $path = '/',
            $domain = '',
            $secure = false,
            $httponly = true
        );
    }

    /**
     * A callable with the following signature
     *
     * @return boolean true for success or false for failure
     */
    public function close(): bool
    {
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
