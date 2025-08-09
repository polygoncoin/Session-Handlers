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
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
namespace CustomSessionHandler\Containers;

use CustomSessionHandler\Containers\SessionContainerInterface;
use CustomSessionHandler\Containers\SessionContainerHelper;

/**
 * Custom Session Handler File
 * php version 7
 *
 * @category  CustomSessionHandler_File
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class FileBasedSessionContainer extends SessionContainerHelper
    implements SessionContainerInterface
{
    public $sessionSavePath = null;

    private $_sessionFilePrefix = 'sess_';

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
        if (!is_dir(filename: $sessionSavePath)) {
            mkdir(directory: $sessionSavePath, permissions: 0755, recursive: true);
        }
        $this->sessionSavePath = $sessionSavePath;
        $this->currentTimestamp = time();
    }

    /**
     * For Custom Session Handler - Validate session ID
     *
     * @param string $sessionId Session ID
     *
     * @return bool|string
     */
    public function get($sessionId): bool|string
    {
        
        $filepath = $this->sessionSavePath . '/' .
            $this->_sessionFilePrefix . $sessionId;

        if (file_exists(filename: $filepath)) {
            $fileatime = fileatime(filename: $filepath);
            if (($this->currentTimestamp - $fileatime) < $this->sessionMaxlifetime) {
                return $this->decryptData(
                    cipherText: file_get_contents(filename: $filepath)
                );
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
    public function set($sessionId, $sessionData): bool|int
    {
        $filepath = $this->sessionSavePath . '/' .
            $this->_sessionFilePrefix . $sessionId;
        if (!file_exists(filename: $filepath)) {
            touch(filename: $filepath);
        }
        return file_put_contents(
            filename: $filepath,
            data: $this->encryptData(plainText: $sessionData)
        );
    }

    /**
     * For Custom Session Handler - Update session timestamp
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool
     */
    public function touch($sessionId, $sessionData): bool
    {
        $filepath = $this->sessionSavePath . '/' .
            $this->_sessionFilePrefix . $sessionId;
        return touch(filename: $filepath);
    }

    /**
     * For Custom Session Handler - Cleanup old sessions
     *
     * @param integer $sessionMaxLifetime Session Max Lifetime
     *
     * @return bool
     */
    public function gc($sessionMaxLifetime): bool
    {
        $datetime = date(
            format: 'Y-m-dTH:i:s+0000',
            timestamp: ($this->currentTimestamp - $sessionMaxLifetime)
        );
        shell_exec(
            command: "find {$this->sessionSavePath} -name \
                '{$this->_sessionFilePrefix}*' -type f -not -newermt \
                '{$datetime}' -delete"
        );
        return true;
    }

    /**
     * For Custom Session Handler - Destroy a session
     *
     * @param string $sessionId Session ID
     *
     * @return bool
     */
    public function delete($sessionId): bool
    {
        $filepath = $this->sessionSavePath . '/' .
            $this->_sessionFilePrefix . $sessionId;
        if (file_exists(filename: $filepath)) {
            unlink(filename: $filepath);
        }
        return true;
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function close(): void
    {
    }
}
