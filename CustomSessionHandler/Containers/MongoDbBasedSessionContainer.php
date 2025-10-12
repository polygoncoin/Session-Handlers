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
 * @category  CustomSessionHandler_MongoDb
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class MongoDbBasedSessionContainer extends SessionContainerHelper implements
    SessionContainerInterface
{
    // "mongodb://<username>:<password>@<cluster-url>:<port>/<database-name>
    // ?retryWrites=true&w=majority"
    public $MONGODB_URI = null;

    public $MONGODB_HOSTNAME = null;
    public $MONGODB_PORT = null;
    public $MONGODB_USERNAME = null;
    public $MONGODB_PASSWORD = null;
    public $MONGODB_DATABASE = null;
    public $MONGODB_COLLECTION = null;

    private $mongo = null;
    private $database = null;
    private $collection = null;

    private $foundSession = false;

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
    public function get($sessionId): bool|string
    {
        $this->foundSession = false;
        if ($this->getKey($sessionId)) {
            $this->foundSession = true;
            return $this->decryptData(cipherText: $this->getKey(key: $sessionId));
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
        return $this->setKey(
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
        return $this->resetExpire(key: $sessionId);
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
        return $this->deleteKey(key: $sessionId);
    }

    /**
     * Close File Container
     *
     * @return void
     */
    public function close(): void
    {
        $this->mongo = null;
    }

    /**
     * Connect
     *
     * @return void
     */
    private function connect(): void
    {
        try {
            if ($this->MONGODB_URI === null) {
                $UP = '';
                if ($this->MONGODB_USERNAME !== null && $this->MONGODB_PASSWORD !== null) {
                    $UP = "{$this->MONGODB_USERNAME}:{$this->MONGODB_PASSWORD}@";
                }
                $this->MONGODB_URI = 'mongodb://' . $UP .
                    $this->MONGODB_HOSTNAME . ':' . $this->MONGODB_PORT;
            }
            $this->mongo = new \MongoDB\Client($this->MONGODB_URI);

            // Select a database
            $this->database = $this->mongo->selectDatabase($this->MONGODB_DATABASE);

            // Select a collection
            $this->collection = $this->database->selectCollection($this->MONGODB_COLLECTION);
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
    }

    /**
     * Set Key
     *
     * @param string $key   Key
     * @param string $value Value
     *
     * @return mixed
     */
    private function setKey($key, $value): bool
    {
        try {
            if ($this->foundSession) {
                $filter = ['sessionId' => $key];
                $update = [
                    '$set' => [
                        'lastAccessed' => $this->currentTimestamp,
                        "sessionData" => $this->encryptData(plainText: $value)
                    ]
                ];
                if ($this->collection->updateOne($filter, $update)) {
                    return true;
                }
            } else {
                $document = [
                    "sessionId" => $key,
                    "lastAccessed" => $this->currentTimestamp,
                    "sessionData" => $this->encryptData(plainText: $value)
                ];
                if ($this->collection->insertOne($document)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
        }
        return false;
    }

    /**
     * Get Key
     *
     * @param string $key Key
     *
     * @return mixed
     */
    private function getKey($key): mixed
    {
        $row = [];
        try {
            $filter = ['sessionId' => $key];

            if ($document = $this->collection->findOne($filter)) {
                $lastAccessed = $this->currentTimestamp - $this->sessionMaxLifetime;
                if ($document['lastAccessed'] > $lastAccessed) {
                    return $this->decryptData(cipherText: $document['sessionData']);
                }
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
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
    private function resetExpire($key): bool
    {
        try {
            $filter = ['sessionId' => $key];
            $update = [
                '$set' => [
                    'lastAccessed' => $this->currentTimestamp
                ]
            ];

            if ($this->collection->updateOne($filter, $update)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
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
    private function deleteKey($key): bool
    {
        try {
            $filter = ['sessionId' => $key];

            if ($this->collection->deleteOne($filter)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->manageException(e: $e);
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
    private function manageException(\Exception $e): never
    {
        die($e->getMessage());
    }
}
