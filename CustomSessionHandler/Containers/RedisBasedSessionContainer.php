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
 * Custom Session Handler using Redis
 * php version 7
 *
 * @category  CustomSessionHandler_Redis
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class RedisBasedSessionContainer extends SessionContainerHelper
    implements SessionContainerInterface
{
    public $REDIS_HOSTNAME = null;
    public $REDIS_PORT = null;
    public $REDIS_USERNAME = null;
    public $REDIS_PASSWORD = null;
    public $REDIS_DATABASE = null;

    private $_redis = null;

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
        if ($this->_redis->exists($sessionId)) {
            return $this->decryptData(cipherText: $this->_getKey(key: $sessionId));
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
        $this->_redis = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function _connect(): void
    {
        try {
            if (!extension_loaded(extension: 'redis')) {
                throw new \Exception(
                    message: "Unable to find Redis extension",
                    code: 500
                );
            }

            $this->_redis = new \Redis(
                [
                    'host' => $this->REDIS_HOSTNAME,
                    'port' => (int)$this->REDIS_PORT,
                    'connectTimeout' => 2.5,
                    'auth' => [$this->REDIS_USERNAME, $this->REDIS_PASSWORD],
                ]
            );
            $this->_redis->select($this->REDIS_DATABASE);
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
        $row = [];
        try {
            if ($data = $this->_redis->get($key)) {
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
     * @return mixed
     */
    private function _setKey($key, $value): bool
    {
        try {
            if ($this->_redis->set($key, $value, $this->sessionMaxLifetime)) {
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
            if ($this->_redis->expire($key, $this->sessionMaxLifetime)) {
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
            if ($this->_redis->del($key)) {
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
