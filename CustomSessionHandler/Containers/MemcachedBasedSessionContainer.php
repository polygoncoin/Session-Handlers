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
 * Custom Session Handler using Memcached
 * php version 7
 *
 * @category  CustomSessionHandler_MemcacheD
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class MemcachedBasedSessionContainer extends SessionContainerHelper
    implements SessionContainerInterface
{
    public $MEMCACHED_HOSTNAME = null;
    public $MEMCACHED_PORT = null;

    private $_memcacheD = null;

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
        $this->_connect();
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
        if ($data = $this->_getKey(key: $sessionId)) {
            return $this->decryptData(cipherText: $data);
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
        return $this->_setKey(
            key: $sessionId,
            value: $this->encryptData(plainText: $sessionData)
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
        return $this->_resetExpire(key: $sessionId);
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
        return $this->_deleteKey(key: $sessionId);
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function close(): void
    {
        $this->_memcacheD = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function _connect(): void
    {
        try {
            if (!extension_loaded(extension: 'memcached')) {
                throw new \Exception(
                    message: "Unable to find Memcached extension",
                    code: 500
                );
            }

            $this->_memcacheD = new \Memcached(); // phpcs:ignore
            $this->_memcacheD->addServer(
                $this->MEMCACHED_HOSTNAME,
                $this->MEMCACHED_PORT
            );
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
        }
    }

    /**
     * Get Key
     *
     * @param string $key Key
     *
     * @return mixed
     */
    private function _getKey($key): mixed
    {
        try {
            if ($data = $this->_memcacheD->get($key)) {
                return $data;
            }
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
        }
        return false;
    }

    /**
     * Set Key
     *
     * @param string $key   Key
     * @param string $value Value
     *
     * @return bool
     */
    private function _setKey($key, $value): bool
    {
        try {
            if ($this->_memcacheD->set($key, $value, $this->sessionMaxLifetime)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
        }
        return false;
    }

    /**
     * Reset Expiry
     *
     * @param string $key Key
     *
     * @return bool
     */
    private function _resetExpire($key): bool
    {
        try {
            if ($this->_memcacheD->touch($key, $this->sessionMaxLifetime)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
        }
        return false;
    }

    /**
     * Delete Key
     *
     * @param string $key Key
     *
     * @return bool
     */
    private function _deleteKey($key): bool
    {
        try {
            if ($this->_memcacheD->delete($key)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->_manageException(e: $e);
        }
        return false;
    }

    /**
     * Manage Exception
     *
     * @param \Exception $e Exception
     *
     * @return never
     */
    private function _manageException(\Exception $e): never
    {
        die($e->getMessage());
    }
}
