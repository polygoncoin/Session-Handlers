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

namespace CustomSessionHandler\Containers;

use CustomSessionHandler\Containers\SessionContainerInterface;
use CustomSessionHandler\Containers\SessionContainerHelper;

/**
 * Custom Session Handler using Cookie
 * php version 7
 *
 * @category  CustomSessionHandler_Cookie
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class CookieBasedSessionContainer extends SessionContainerHelper implements
    SessionContainerInterface
{
    /**
     * Initialize
     *
     * @param string $sessionSavePath Session Save Path
     * @param string $sessionName     Session Name
     *
     * @return void
     */
    public function init($sessionSavePath, $sessionName): void
    {
        if (empty($this->passphrase) || empty($this->iv)) {
            die('Please set encryption details in Session.php');
        }

        $this->currentTimestamp = time();
    }

    /**
     * For Custom Session Handler - Validate session ID
     *
     * @param string $sessionId Session ID
     *
     * @return bool|string
     */
    public function getSession($sessionId): bool|string
    {
        if (
            isset($_COOKIE[$this->sessionDataName])
            && !empty($_COOKIE[$this->sessionDataName])
        ) {
            $sessionData = $this->decryptData(
                cipherText: $_COOKIE[$this->sessionDataName]
            );
            $sessionDataArr = unserialize(data: $sessionData);
            if (
                isset($sessionDataArr['_TS_'])
                && ($time = $sessionDataArr['_TS_'] + $this->sessionMaxLifetime)
                && $time > $this->currentTimestamp
            ) {
                return $sessionData;
            }
        }
        return false;
    }

    /**
     * For Custom Session Handler - Write session data
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function setSession($sessionId, $sessionData): bool|int
    {
        $sessionDataArr = unserialize(data: $sessionData);
        $sessionDataArr['_TS_'] = $this->currentTimestamp;
        $sessionData = serialize(value: $sessionDataArr);

        $cookieData = $this->encryptData(plainText: $sessionData);
        if (strlen(string: $cookieData) > 4096) {
            ob_end_clean();
            die(
                'Session data length exceeds max 4 kilobytes (KB)' .
                ' supported per Cookie'
            );
        }

        $_COOKIE[$this->sessionDataName] = $cookieData;

        return setcookie(
            name: $this->sessionDataName,
            value: $cookieData,
            expires_or_options: [
                'expires' => 0,
                'path' => $this->sessionOptions['cookie_path'],
                'domain' => '',
                'secure' => (
                    (
                        strpos(
                            haystack: $_SERVER['HTTP_HOST'],
                            needle: 'localhost'
                        ) === false
                    ) ? true : false
                ),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * For Custom Session Handler - Update session data
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function updateSession($sessionId, $sessionData): bool|int
    {
        return $this->setSession(sessionId: $sessionId, sessionData: $sessionData);
    }

    /**
     * For Custom Session Handler - Update session timestamp
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool
     */
    public function touchSession($sessionId, $sessionData): bool
    {
        $sessionDataArr = unserialize(data: $sessionData);
        $sessionDataArr['_TS_'] = $this->currentTimestamp;
        $sessionData = serialize(value: $sessionDataArr);

        $cookieData = $this->encryptData(plainText: $sessionData);
        if (strlen(string: $cookieData) > 4096) {
            ob_end_clean();
            die(
                'Session data length exceeds max 4 kilobytes (KB)' .
                ' supported per Cookie'
            );
        }

        $_COOKIE[$this->sessionDataName] = $cookieData;

        return setcookie(
            name: $this->sessionDataName,
            value: $cookieData,
            expires_or_options: [
                'expires' => 0,
                'path' => $this->sessionOptions['cookie_path'],
                'domain' => '',
                'secure' => (
                    (
                        strpos(
                            haystack: $_SERVER['HTTP_HOST'],
                            needle: 'localhost'
                        ) === false
                    ) ? true : false
                ),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * For Custom Session Handler - Cleanup old sessions
     *
     * @param integer $sessionMaxLifetime Session Max Lifetime
     *
     * @return bool
     */
    public function gcSession($sessionMaxLifetime): bool
    {
        return true;
    }

    /**
     * For Custom Session Handler - Destroy a session
     *
     * @param string $sessionId Session ID
     *
     * @return bool
     */
    public function deleteSession($sessionId): bool
    {
        if (isset($_COOKIE[$this->sessionDataName])) {
            unset($_COOKIE[$this->sessionDataName]);
        }
        return true;
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function closeSession(): void
    {
    }
}
