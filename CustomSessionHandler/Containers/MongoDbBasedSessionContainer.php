<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
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
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class MongoDbBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	// "mongodb://<username>:<password>@<cluster-url>:<port>/<database-name>
	// ?retryWrites=true&w=majority"
	public $mongoDbServerUri = null;

	public $sessionServerHost = null;
	public $sessionServerPort = null;
	public $sessionServerUser = null;
	public $sessionServerPassword = null;
	public $sessionServerDb = null;
	public $sessionServerTable = null;

	private $sessionServerObject = null;
	private $dbObject = null;
	private $collectionObject = null;


	/**
	 * Constructor
	 */
	public function __construct() {
		
	}

	/**
	 * Initialize
	 * 
	 * @param string $sessionSavePath Session Save Path
	 * @param string $sessionName     Session Name
	 * 
	 * @return void
	 */
	public function init(
		$sessionSavePath,
		$sessionName
	): void {
		$this->connect();
	}

	/**
	 * For Custom Session Handler - Validate session id
	 * 
	 * @param string $sessionId Session id
	 * 
	 * @return bool|string
	 */
	public function getSession(
		$sessionId
	): bool|string {
		try {
			$filter = ['sessionId' => $sessionId];

			if ($document = $this->collectionObject->findOne($filter)) {
				$lastAccessed = $this->ENV->timestamp - $this->sessionOptions['cookie_lifetime'];
				if ($document['lastAccessed'] > $lastAccessed) {
					$sessionData = $this->decryptData(
						cipherText: $document['sessionData']
					);
					return $sessionData;
				}
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return false;
	}

	/**
	 * For Custom Session Handler - Write session data
	 * 
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 * 
	 * @return bool|int
	 */
	public function setSession(
		$sessionId,
		$sessionData
	): bool|int {
		$sessionDataArray = unserialize(
			data: $sessionData
		);

		
		$sessionData = serialize(
			value: $sessionDataArray
		);

		try {
			$document = [
				"sessionId" => $sessionId,
				"lastAccessed" => $this->ENV->timestamp,
				"sessionData" => $this->encryptData(
					plainText: $sessionData
				)
			];
			if ($this->collectionObject->insertOne($document)) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return false;
	}

	/**
	 * Update Session
	 * 
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 * 
	 * @return bool|int
	 */
	public function updateSession(
		$sessionId,
		$sessionData
	): bool|int {
		$sessionDataArray = unserialize(
			data: $sessionData
		);

		
		$sessionData = serialize(
			value: $sessionDataArray
		);

		try {
			$filter = ['sessionId' => $sessionId];
			$update = [
				'$set' => [
					'lastAccessed' => $this->ENV->timestamp,
					"sessionData" => $this->encryptData(
						plainText: $sessionData
					)
				]
			];
			if (
				$this->collectionObject->updateOne(
					$filter,
					$update
				)
			) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return false;
	}

	/**
	 * For Custom Session Handler - Update session timestamp
	 * 
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 * 
	 * @return bool
	 */
	public function touchSession(
		$sessionId,
		$sessionData
	): bool {
		$sessionDataArray = unserialize(
			data: $sessionData
		);

		
		$sessionData = serialize(
			value: $sessionDataArray
		);

		try {
			$filter = ['sessionId' => $sessionId];
			$update = [
				'$set' => [
					'lastAccessed' => $this->ENV->timestamp
				]
			];

			if (
				$this->collectionObject->updateOne(
					$filter,
					$update
				)
			) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
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
	public function gcSession(
		$sessionMaxLifetime
	): bool {
		return true;
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 * 
	 * @param string $sessionId Session id
	 * 
	 * @return bool
	 */
	public function deleteSession(
		$sessionId
	): bool {
		try {
			$filter = ['sessionId' => $sessionId];

			if ($this->collectionObject->deleteOne($filter)) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return false;
	}

	/**
	 * Close File Containers
	 * 
	 * @return void
	 */
	public function closeSession(): void
	{
		$this->sessionServerObject = null;
	}

	/**
	 * Connect
	 * 
	 * @return void
	 */
	private function connect(): void
	{
		try {
			if ($this->mongoDbServerUri === null) {
				$UP = '';
				if (
					$this->sessionServerUser !== null
					&& $this->sessionServerPassword !== null
				) {
					$UP = "{$this->sessionServerUser}:{$this->sessionServerPassword}@";
				}
				$this->mongoDbServerUri = 'mongodb://' . $UP
					. $this->sessionServerHost . ':' . $this->sessionServerPort;
			}
			$this->sessionServerObject = new \MongoDB\Customer(
				$this->mongoDbServerUri
			);

			// Select a database
			$this->dbObject = $this->sessionServerObject->selectDatabase(
				$this->sessionServerDb
			);

			// Select a collection
			$this->collectionObject = $this->dbObject->selectCollection(
				$this->sessionServerTable
			);
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
	}

	/**
	 * Manage Exception
	 * 
	 * @param \Exception $e Exception
	 * 
	 * @return never
	 */
	private function manageException(
		\Exception $e
	): never {
		throw new \Exception(
			message: $e->getMessage(),
			code: 500
		);
	}
}
