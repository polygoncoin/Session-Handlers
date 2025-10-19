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
 * Custom Session Handler using Redis
 * php version 7
 *
 * @category  CustomSessionHandler_Redis
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class RedisBasedSessionContainer extends SessionContainerHelper implements
    SessionContainerInterface
{
    public $REDIS_HOSTNAME = null;
    public $REDIS_PORT = null;
    public $REDIS_USERNAME = null;
    public $REDIS_PASSWORD = null;
    public $REDIS_DATABASE = null;

    private $redis = null;

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
        $this->connect();
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
        try {
            if (
                $this->redis->exists($sessionId)
                && ($data = $this->redis->get($sessionId))
            ) {
                return $this->decryptData(cipherText: $data);
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
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
        try {
            if (
                $this->redis->set(
                    $sessionId,
                    $this->encryptData(plainText: $sessionData),
                    $this->sessionMaxLifetime
                )
            ) {
                return true;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return false;
    }

    /**
     * Update Session
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function updateSession($sessionId, $sessionData): bool|int
    {
        return $this->setSession(
            sessionId: $sessionId,
            sessionData: $sessionData
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
    public function touchSession($sessionId, $sessionData): bool
    {
        try {
            if ($this->redis->expire($sessionId, $this->sessionMaxLifetime)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return false;
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
        try {
            if ($this->redis->del($sessionId)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return false;
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function closeSession(): void
    {
        $this->redis = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function connect(): void
    {
        try {
            if (!extension_loaded(extension: 'redis')) {
                throw new \Exception(
                    message: "Unable to find Redis extension",
                    code: 500
                );
            }

            $connParams = [
                'host' => $this->REDIS_HOSTNAME,
                'port' => (int)$this->REDIS_PORT,
                'connectTimeout' => 2.5
            ];

            if (
                $this->REDIS_USERNAME !== null
                && $this->REDIS_PASSWORD !== null
            ) {
                $connParams['auth'] = [
                    $this->REDIS_USERNAME,
                    $this->REDIS_PASSWORD
                ];
            }

            $this->redis = new \Redis( // phpcs:ignore
                $connParams
            );
            $this->redis->select($this->REDIS_DATABASE);
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
    }

    /**
     * Manage Exception
     *
     * @param \Exception $e Exception
     *
     * @return never
     */
    private function manageException(\Exception $e): never
    {
        die($e->getMessage());
    }
}
