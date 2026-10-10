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

namespace CustomSessionHandler;

use CustomSessionHandler\CustomSessionHandler;
use CustomSessionHandler\Containers\SessionContainerInterface;
use CustomSessionHandler\Environment;

/**
 * Custom Session Handler Config
 * php version 7
 *
 * @category  CustomSessionHandler_Config
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class Session
{
	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION PASS PHRASE
	 * 
	 * Value = base64_encode(openssl_random_pseudo_bytes(32))
	 * Example: public $sessionEncryptionPassPhrase =
	 * 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
	 * 
	 * @var null|string
	 */
	public $sessionEncryptionPassPhrase = null;

	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION IV
	 * 
	 * Value = base64_encode(openssl_random_pseudo_bytes(16))
	 * Example: public $sessionEncryptionIv = 'HnPG5az9Xaxam9G9tMuRaw==';
	 * 
	 * @var null|string
	 */
	public $sessionEncryptionIv = null;

	/**
	 * Session customer id
	 * 
	 * @var null|int
	 */
	public $customerId = null;

	/**
	 * Session handler Container
	 * 
	 * @var null|SessionContainerInterface
	 */
	public $sessionContainer = null;

	/**
	 * Environment data
	 * 
	 * @var null|Environment
	 */
	public $ENV = null;

	/**
	 * Constructor
	 * 
	 * @param string $envFile Enviroment file name
	 */
	public function __construct(
		$envFile
	) {
		$this->ENV = new Environment($envFile);
	}

	/**
	 * Initialize container
	 * 
	 * @return SessionContainerInterface
	 */
	private function getSessionContainer(): SessionContainerInterface
	{
		// Initialize Container
		$containerClassName = 'CustomSessionHandler\\Containers\\' .
			ucfirst($this->ENV->SESSION_STORE_MODE) . 'BasedSessionContainer';
		$this->sessionContainer = new $containerClassName();

		// Setting encryption parameters
		$this->sessionContainer->SESSION_ENCRYPTION_PASS_PHRASE = base64_decode(
			string: $this->ENV->SESSION_ENCRYPTION_PASS_PHRASE
		);

		$this->sessionContainer->SESSION_ENCRYPTION_IV = base64_decode(
			string: $this->ENV->SESSION_ENCRYPTION_IV
		);

		$this->sessionContainer->sessionName = $this->ENV->SESSION_COOKIE_NAME;
		$this->sessionContainer->ENV = $this->ENV;

		// Setting required parameters as per session Mode / Type
		switch ($this->ENV->SESSION_STORE_MODE) {
			case 'MySql':
				$this->sessionContainer->sessionServerHost = $this->ENV->SESSION_MYSQL_HOST;
				$this->sessionContainer->sessionServerPort = $this->ENV->SESSION_MYSQL_PORT;
				$this->sessionContainer->sessionServerUser = $this->ENV->SESSION_MYSQL_USER;
				$this->sessionContainer->sessionServerPassword = $this->ENV->SESSION_MYSQL_PASSWORD;
				$this->sessionContainer->sessionServerDb = $this->ENV->SESSION_MYSQL_DB;
				$this->sessionContainer->sessionServerTable = $this->ENV->SESSION_MYSQL_TABLE;
				break;
			case 'PostgreSql':
				$this->sessionContainer->sessionServerHost = $this->ENV->SESSION_PGSQL_HOST;
				$this->sessionContainer->sessionServerPort = $this->ENV->SESSION_PGSQL_PORT;
				$this->sessionContainer->sessionServerUser = $this->ENV->SESSION_PGSQL_USER;
				$this->sessionContainer->sessionServerPassword = $this->ENV->SESSION_PGSQL_PASSWORD;
				$this->sessionContainer->sessionServerDb = $this->ENV->SESSION_PGSQL_DB;
				$this->sessionContainer->sessionServerTable = $this->ENV->SESSION_PGSQL_TABLE;
				break;
			case 'MongoDb':
				$this->sessionContainer->sessionServerHost = $this->ENV->SESSION_MONGO_HOST;
				$this->sessionContainer->sessionServerPort = $this->ENV->SESSION_MONGO_PORT;
				$this->sessionContainer->sessionServerUser = $this->ENV->SESSION_MONGO_USER;
				$this->sessionContainer->sessionServerPassword = $this->ENV->SESSION_MONGO_PASSWORD;
				$this->sessionContainer->sessionServerDb = $this->ENV->SESSION_MONGO_DB;
				$this->sessionContainer->sessionServerCollection = $this->ENV->SESSION_MONGO_TABLE;
				break;
			case 'Redis':
				$this->sessionContainer->sessionServerHost = $this->ENV->SESSION_REDIS_HOST;
				$this->sessionContainer->sessionServerPort = $this->ENV->SESSION_REDIS_PORT;
				$this->sessionContainer->sessionServerUser = $this->ENV->SESSION_REDIS_USER;
				$this->sessionContainer->sessionServerPassword = $this->ENV->SESSION_REDIS_PASSWORD;
				$this->sessionContainer->sessionServerDb = $this->ENV->SESSION_REDIS_DB;
				break;
			case 'Memcached':
				$this->sessionContainer->sessionServerHost = $this->ENV->SESSION_MEMCACHED_HOST;
				$this->sessionContainer->sessionServerPort = $this->ENV->SESSION_MEMCACHED_PORT;
				break;
			case 'Cookie':
                $this->sessionContainer->sessionDataCookieName = $this->ENV->SESSION_DATA_COOKIE_NAME;
				break;
		}

		return $this->sessionContainer;
	}

	/**
	 * Generates session optionArray argument
	 * 
	 * @param bool $readonly Readonly mode
	 * 
	 * @return array
	 */
	private function getSessionOptions(
		$readonly
	): array {
		$options = [ // always required.
			'use_strict_mode' => true,
			'use_cookies' => true,
			'name' => $this->ENV->SESSION_COOKIE_NAME,
			'serialize_handler' => 'php_serialize',
			'lazy_write' => true,
			'cookie_lifetime' => (int)$this->ENV->SESSION_LIFETIME,
			'cookie_path' => $this->ENV->SESSION_COOKIE_PATH,
			'cookie_secure' => (bool)$this->ENV->SESSION_COOKIE_SECURE,
			'cookie_httponly' => (bool)$this->ENV->SESSION_COOKIE_HTTPONLY,
			'cookie_samesite' => $this->ENV->SESSION_COOKIE_SAMESITE,
		];

		if ($this->ENV->SESSION_STORE_MODE === 'File') {
			$options['save_path'] = __DIR__ . DIRECTORY_SEPARATOR . $this->ENV->SESSION_STORE_PATH;
		}

		if ($readonly === true) {
			$options['read_and_close'] = true;
		}

		// Setting required common parameters
		$this->sessionContainer->sessionOptions = $options;

		return $options;
	}

	/**
	 * Initialize session handler
	 * 
	 * @return void
	 */
	public function initSessionHandler(): void
	{
		// Initialize container
		$this->sessionContainer = $this->getSessionContainer();

		$customSessionHandler = new CustomSessionHandler(
			container: $this->sessionContainer
		);

		session_set_save_handler(
			$customSessionHandler,
			true
		);

	}

	/**
	 * Close if Session is Active in write mode
	 * 
	 * @return void
	 */
	public function closeActiveSession(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
	}

	/**
	 * Start session in read only mode
	 * 
	 * @return bool
	 */
	public function startReadonly(): bool
	{
		$this->closeActiveSession();

		if (
			!isset($_COOKIE[$this->ENV->SESSION_COOKIE_NAME])
			|| empty($_COOKIE[$this->ENV->SESSION_COOKIE_NAME])
		) {
			return false;
		}

		$currentSessionId = $_COOKIE[$this->ENV->SESSION_COOKIE_NAME];
		session_id($currentSessionId);

		return session_start(
			options: $this->getSessionOptions(
				readonly: true
			)
		);
	}

	/**
	 * Start session in read/write mode
	 * 
	 * @return bool
	 */
	public function startReadWrite(): bool
	{
		$this->closeActiveSession();

		if (
			isset($_COOKIE[$this->ENV->SESSION_COOKIE_NAME])
			|| !empty($_COOKIE[$this->ENV->SESSION_COOKIE_NAME])
		) {
			$currentSessionId = $_COOKIE[$this->ENV->SESSION_COOKIE_NAME];
			session_id($currentSessionId);
		}

		return session_start(
			options: $this->getSessionOptions(
				readonly: false
			)
		);
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
		return $this->sessionContainer->deleteSession(
			$sessionId
		);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 * 
	 * @param array $sessionIds Session IDs
	 * 
	 * @return void
	 */
	public function deleteSessions(
		$sessionIds
	): void {
		$indexCount = count(
			value: $sessionIds
		);
		for ($index = 0; $index < $indexCount; $index++) {
			$this->deleteSession(
				$sessionIds[$index]
			);
		}
	}
}
