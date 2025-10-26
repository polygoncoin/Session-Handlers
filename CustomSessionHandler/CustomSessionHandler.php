<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */

namespace CustomSessionHandler;

use CustomSessionHandler\Containers\SessionContainerInterface;

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  CustomSessionHandler
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class CustomSessionHandler implements
    \SessionHandlerInterface,
    \SessionIdInterface,
    \SessionUpdateTimestampHandlerInterface
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
     * Session found
     *
     * @var null|bool
     */
    private $foundSession = null;

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
     * @var null|bool
     */
    private $creatingSessionId = null;

    /**
     * Session Data
     *
     * @var null|string
     */
    private $sessionData = '';

    /**
     * _isTimestampUpdated flag for read_and_close or readonly session behaviour
     * To be careful with the 'read_and_close' option
     * It doesn't update the session last modification timestamp
     * unlike the default PHP behaviour
     *
     * @var bool
     */
    private $isTimestampUpdated = false;

    /**
     * Constructor
     *
     * @param SessionContainerInterface $container Container
     */
    public function __construct(&$container)
    {
        $this->container = &$container;
    }

    /**
     * Open session
     * A callable with the following signature
     *
     * @param string $sessionSavePath Save Path
     * @param string $sessionName     Session Name
     *
     * @return bool true for success or false for failure
     */
    public function open($sessionSavePath, $sessionName): bool
    {
        $this->container->init(
            sessionSavePath: $sessionSavePath,
            sessionName: $sessionName
        );

        return true;
    }

    /**
     * Validate session ID
     *
     * Calls if session cookie is present in request
     *
     * A callable with the following signature
     *
     * @param string $sessionId Session ID
     *
     * @return bool true if the session id is valid otherwise false
     */
    public function validateId($sessionId): bool
    {
        if ($sessionData = $this->container->getSession(sessionId: $sessionId)) {
            if (is_null(value: $this->creatingSessionId)) {
                $this->sessionData = &$sessionData;
            }
            $this->foundSession = true;
        } else {
            if (is_null(value: $this->creatingSessionId)) {
                $this->unsetSessionCookie();
            }
            $this->foundSession = false;
        }

        // Don't change this return value
        return $this->foundSession;
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
    public function create_sid(): string // phpcs:ignore
    {
        // Delete session if previous sessionId exist eg; used for
        // session_regenerate_id()
        if (!empty($this->sessionId)) {
            $this->container->deleteSession(sessionId: $this->sessionId);
        }

        $this->creatingSessionId = true;

        do {
            $sessionId = $this->getRandomString();
        } while ($this->validateId(sessionId: $sessionId) === true);

        $this->creatingSessionId = null;

        return $sessionId;
    }

    /**
     * Read session data
     *
     * A callable with the following signature
     *
     * @param string $sessionId Session ID
     *
     * @return string|false the session data or an empty string
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
     * @param string $sessionId   Session Id
     * @param string $sessionData Session Data
     *
     * @return bool true for success or false for failure
     */
    public function write($sessionId, $sessionData): bool
    {
        $this->sessionData = $sessionData;
        // Won't allow creating empty entries
        // unless previous data is not empty
        if (empty($sessionData) && empty(unserialize(data: $sessionData))) {
            $this->unsetSessionCookie();
            return true;
        }

        $fn = ($this->foundSession) ? 'updateSession' : 'setSession';
        if (
            $this->container->$fn(
                sessionId: $sessionId,
                sessionData: $sessionData
            )
        ) {
            $this->isTimestampUpdated = true;
        }

        return $this->isTimestampUpdated;
    }

    /**
     * Update session timestamp
     *
     * When session.lazy_write is enabled, and session data is unchanged
     * UpdateTimestamp is called instead (of write) to only update the timestamp
     * of session
     *
     * A callable with the following signature
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool true for success or false for failure
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        $this->sessionData = $sessionData;
        // Won't allow updating empty entries when session.lazy_write is enabled
        // unless previous data is not empty
        if (empty($sessionData) && empty(unserialize(data: $sessionData))) {
            $this->unsetSessionCookie();
            return true;
        }

        if (
            $this->container->touchSession(
                sessionId: $sessionId,
                sessionData: $sessionData
            )
        ) {
            $this->isTimestampUpdated = true;
        }

        return $this->isTimestampUpdated;
    }

    /**
     * Cleanup old sessions
     *
     * A callable with the following signature
     *
     * @param integer $sessionMaxLifetime Session life time
     *
     * @return bool true for success or false for failure
     */
    public function gc($sessionMaxLifetime): int|false
    {
        return $this->container->gcSession(sessionMaxLifetime: $sessionMaxLifetime);
    }

    /**
     * Destroy a session
     *
     * A callable with the following signature
     *
     * @param string $sessionId Session ID
     *
     * @return bool true for success or false for failure
     */
    public function destroy($sessionId): bool
    {
        // Deleting session cookies set on client end
        $this->unsetSessionCookie();

        return $this->container->deleteSession(sessionId: $sessionId);
    }

    /**
     * Close the session
     *
     * A callable with the following signature
     *
     * @return bool true for success or false for failure
     */
    public function close(): bool
    {
        // Updating timestamp for readonly mode (read_and_close option)
        if (!$this->isTimestampUpdated && $this->foundSession === true) {
            $this->container->touchSession(
                sessionId: $this->sessionId,
                sessionData: $this->sessionData
            );
        }

        $this->resetUniqueCookieHeaders();

        $this->container->closeSession();
        $this->sessionData = '';
        $this->foundSession = null;
        $this->isTimestampUpdated = false;

        return true;
    }

    /**
     * Returns 64 char random string
     *
     * @return string
     */
    private function getRandomString(): string
    {
        return bin2hex(string: random_bytes(length: 32));
    }

    /**
     * Unset session cookies
     *
     * @return void
     */
    private function unsetSessionCookie(): void
    {
        if (!empty($this->sessionName)) {
            setcookie(
                name: $this->sessionName,
                value: '',
                expires_or_options: 1,
                path: $this->container->sessionOptions['cookie_path']
            );
        }
        if (!empty($this->sessionDataName)) {
            setcookie(
                name: $this->sessionDataName,
                value: '',
                expires_or_options: 1,
                path: $this->container->sessionOptions['cookie_path']
            );
        }
    }

    /**
     * Set Unique Cookie Headers
     *
     * @return void
     */
    private function resetUniqueCookieHeaders(): void
    {
        // Check header is sent.
        if (headers_sent()) {
            return;
        }

        $headers = [];

        // Collect Cookie headers
        foreach (headers_list() as $header) {
            // Check for Cookie header
            if (strpos(haystack: $header, needle: 'Set-Cookie:') === 0) {
                $headers[] = $header;
            }
        }

        // Remove all Set-Cookie headers
        header_remove(name: 'Set-Cookie');

        // Set Unique Set-Cookie headers
        for (; $header = array_shift(array: $headers);) {
            if (!in_array(needle: $header, haystack: $headers)) {
                header(header: $header, replace: false);
            }
        }
    }
}
