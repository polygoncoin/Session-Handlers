<?php
/**
 * Class for Custom Session Handler
 *
 * DON'T make any changes in this class
 * Make required changes in Containers
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
    /**
     * Session cookie name
     *
     * @var null|string
     */
    public $sessionName = null;

    /**
     * Session data cookie name
     *
     * @var null|string
     */
    public $sessionDataName = null;

    /**
     * Session Container
     *
     * @var null|SessionContainerInterface
     */
    private $container = null;

    /**
     * Session data found
     *
     * @var null|boolean
    */
    private $dataFound = null;

    /**
     * Session Id
     *
     * @var string
     */
    private $sessionId = '';

    /**
     * Session ID created flag to handle session_regenerate_id
     * In this case validateId is called after create_sid function
     * Also, we have used this to validate created sessionId
     *
     * @var null|boolean
    */
    private $creatingSessionId = null;

    /**
     * Session Data
     *
     * @var null|string
     */
    private $sessionData = '';

    /**
     * updatedSessionTimestamp flag for read_and_close or readonly session behaviour
     * To be careful with the 'read_and_close' option
     * It doesn't update the session last modification timestamp
     * unlike the default PHP behaviour
     *
     * @var boolean
     */
    private $updatedSessionTimestamp = false;

    /**
     * Constructor
     *
     * @param SessionContainerInterface $container
     */
    public function __construct(&$container)
    {
        $this->container = &$container;
    }

    /**
     * Initialize session
     *
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
     * Validate session ID
     *
     * Calls if session cookie is present in request
     *
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return string true if the session id is valid otherwise false
     */
    public function validateId($sessionId): bool
    {
        if ($sessionData = $this->container->get($sessionId)) {
            if (is_null($this->creatingSessionId)) {
                $this->sessionData = &$sessionData;
            }
            $this->dataFound = true;
        } else {
            if (is_null($this->creatingSessionId)) {
                $this->unsetSessionCookie();
            }
            $this->dataFound = false;
        }

        // Don't change this return value
        return $this->dataFound;
    }

    /**
     * Create session ID
     *
     * Calls if no session cookie is present
     * Invoked internally when a new session id is needed
     *
     * A callable with the following signature
     *
     * @return string should be new session id
     */
    public function create_sid(): string
    {
        // Delete session if previous sessionId exist eg; used for session_regenerate_id()
        if (!empty($this->sessionId)) {
            $this->container->delete($this->sessionId);
        }

        $this->creatingSessionId = true;

        do {
            $sessionId = $this->getRandomString();
        } while ($this->validateId($sessionId) === true);

        $this->creatingSessionId = null;

        return $sessionId;
    }

    /**
     * Read session data
     *
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return string the session data or an empty string
     */
    public function read($sessionId): string|false
    {
        $this->sessionId = $sessionId;
        return $this->sessionData;
    }

    /**
     * Write session data
     *
     * When session.lazy_write is enabled, and session data is unchanged
     * it will skip this method call. Instead it will call updateTimestamp
     *
     * A callable with the following signature
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean true for success or false for failure
     */
    public function write($sessionId, $sessionData): bool
    {
        // Won't allow creating empty entries
        // unless previous data is not empty
        if (empty($this->sessionData) && empty(unserialize($sessionData))) {
            $this->unsetSessionCookie();
            return true;
        }

        if ($this->container->set($sessionId, $sessionData)) {
            $this->updatedSessionTimestamp = true;
        }

        return $this->updatedSessionTimestamp;
    }

    /**
     * Update session timestamp
     *
     * When session.lazy_write is enabled, and session data is unchanged
     * UpdateTimestamp is called instead (of write) to only update the timestamp of session
     *
     * A callable with the following signature
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean true for success or false for failure
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        // Won't allow updating empty entries when session.lazy_write is enabled
        // unless previous data is not empty
        if (empty($this->sessionData) && empty(unserialize($sessionData))) {
            $this->unsetSessionCookie();
            return true;
        }

        if ($this->container->touch($sessionId, $sessionData)) {
            $this->updatedSessionTimestamp = true;
        }

        return $this->updatedSessionTimestamp;
    }

    /**
     * Cleanup old sessions
     *
     * A callable with the following signature
     *
     * @param integer $sessionMaxlifetime
     * @return boolean true for success or false for failure
     */
    public function gc($sessionMaxlifetime): int|false
    {
        return $this->container->gc($sessionMaxlifetime);
    }

    /**
     * Destroy a session
     *
     * A callable with the following signature
     *
     * @param string $sessionId
     * @return boolean true for success or false for failure
     */
    public function destroy($sessionId): bool
    {
        // Deleting session cookies set on client end
        $this->unsetSessionCookie();

        return $this->container->delete($sessionId);
    }

    /**
     * Close the session
     *
     * A callable with the following signature
     *
     * @return boolean true for success or false for failure
     */
    public function close(): bool
    {
        // Updating timestamp for readonly mode (read_and_close option)
        if (!$this->updatedSessionTimestamp && $this->dataFound === true) {
            $this->container->touch($this->sessionId, $this->sessionData);
        }

        $this->resetUniqueCookieHeaders();

        $this->container->close();
        $this->sessionData = '';
        $this->dataFound = null;
        $this->updatedSessionTimestamp = false;

        return true;
    }

    /** Destructor */
    public function __destruct()
    {
    }

    /**
     * Returns 64 char random string
     *
     * @return string
     */
    private function getRandomString(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Unset session cookies
     *
     * @return void
     */
    private function unsetSessionCookie()
    {
        if (!empty($this->sessionName)) {
            setcookie($this->sessionName, '', 1);
            setcookie($this->sessionName, '', 1, '/');
        }
        if (!empty($this->sessionDataName)) {
            setcookie($this->sessionDataName,'',1);
            setcookie($this->sessionDataName,'',1, '/');
        }
    }

    private function resetUniqueCookieHeaders()
    {
        // Check header is sent.
        if (headers_sent()) {
            return;
        }

        $headers = [];

        // Collect Cookie headers
        foreach (headers_list() as $header) {
            // Check for Cookie header
            if (strpos($header, 'Set-Cookie:') === 0) {
                $headers[] = $header;
            }
        }

        // Remove all Set-Cookie headers
        header_remove('Set-Cookie');

        // Set Unique Set-Cookie headers
        for(;$header = array_shift($headers);) {
            if (!in_array($header, $headers)) {
                header($header, false);
            }
        }
    }
}
