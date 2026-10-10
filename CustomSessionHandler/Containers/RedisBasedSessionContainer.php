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
 * @category  CustomSessionHandler_Redis
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class RedisBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $sessionServerHost = null;
	public $sessionServerPort = null;
	public $sessionServerUser = null;
	public $sessionServerPassword = null;
	public $sessionServerDb = null;

	private $sessionServerObject = null;


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
			if (
				$this->sessionServerObject->exists($sessionId)
				&& ($data = $this->sessionServerObject->get($sessionId))
			) {
				$sessionData = $this->decryptData(
					cipherText: $data
				);
				return $sessionData;
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
			if (
				$this->sessionServerObject->set(
					$sessionId,
					$this->encryptData(
						plainText: $sessionData
					),
					$this->sessionOptions['cookie_lifetime']
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

		return $this->setSession(
			sessionId: $sessionId,
			sessionData: $sessionData
		);
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
			if (
				$this->sessionServerObject->expire(
					$sessionId,
					$this->sessionOptions['cookie_lifetime']
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
			if ($this->sessionServerObject->del($sessionId)) {
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
			if (
				!extension_loaded(
					extension: 'redis'
				)
			) {
				throw new \Exception(
					message: "Unable to find Redis extension",
					code: 500
				);
			}

			$connParamArray = [
				'host' => $this->sessionServerHost,
				'port' => (int)$this->sessionServerPort,
				'connectTimeout' => 2.5
			];

			if (
				$this->sessionServerUser !== null
				&& $this->sessionServerPassword !== null
			) {
				$connParamArray['auth'] = [
					$this->sessionServerUser,
					$this->sessionServerPassword
				];
			}

			$this->sessionServerObject = new \Redis( // phpcs:ignore
				$connParamArray
			);
			$this->sessionServerObject->select(
				$this->sessionServerDb
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
