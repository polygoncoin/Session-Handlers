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
 * Custom Session Handler using PostgreSql
 * php version 7
 *
 * @category  CustomSessionHandler_PgSql
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $sessionServerHost = null;
	public $sessionServerPort = null;
	public $sessionServerUser = null;
	public $sessionServerPassword = null;
	public $sessionServerDb = null;
	public $sessionServerTable = null;

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
		$sql = "
			SELECT *
			FROM {$this->sessionServerTable}
			WHERE session_id = $1 AND last_accessed > $2
		";
		$paramArray = [
			$sessionId,
			($this->ENV->timestamp - $this->sessionOptions['cookie_lifetime'])
		];

		$record = $this->getSql(
			sql: $sql,
			paramArray: $paramArray
		);
		if (isset($record['session_data'])) {
			$sessionData = $this->decryptData(
				cipherText: $record['session_data']
			);
			return $sessionData;
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
		$sql = "
			INSERT INTO {$this->sessionServerTable} (session_id, last_accessed, session_data)
			VALUES ($1, $2, $3)
		";
		$paramArray = [
			$sessionId,
			$this->ENV->timestamp,
			$this->encryptData(
				plainText: $sessionData
			),
		];

		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * For Custom Session Handler - Update session data
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
		$sql = "
			UPDATE {$this->sessionServerTable}
			SET
				last_accessed = $1,
				session_data = $2
			WHERE
				session_id = $3
		";
		$paramArray = [
			$this->ENV->timestamp,
			$this->encryptData(
				plainText: $sessionData
			),
			$sessionId
		];

		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
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
		$sql = "
			UPDATE {$this->sessionServerTable}
			SET last_accessed = $1
			WHERE session_id = $2
		";
		$paramArray = [
			$this->ENV->timestamp,
			$sessionId
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
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
		$sql = "
			DELETE FROM {$this->sessionServerTable}
			WHERE last_accessed < $1
		";
		$paramArray = [
			($this->ENV->timestamp - $sessionMaxLifetime)
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
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
		$sql = "
			DELETE FROM {$this->sessionServerTable}
			WHERE session_id = $1
		";
		$paramArray = [
			$sessionId
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * Close File Containers
	 * 
	 * @return void
	 */
	public function closeSession(): void
	{
		pg_close(
			$this->sessionServerObject
		);
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
			$UP = '';
			if (
				$this->sessionServerUser !== null
				&& $this->sessionServerPassword !== null
			) {
				$UP = "user={$this->sessionServerUser} password={$this->sessionServerPassword}";
			}
			$this->sessionServerObject = pg_connect(
				"host={$this->sessionServerHost} "
				. "port={$this->sessionServerPort} "
				. "dbname={$this->sessionServerDb} {$UP}"
			);
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
	}

	/**
	 * Get Session
	 * 
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 * 
	 * @return mixed
	 */
	private function getSql(
		$sql,
		$paramArray
	): mixed {
		try {
			// Execute the query with parameters
			$result = pg_query_params(
				$this->sessionServerObject,
				$sql,
				$paramArray
			);
			if ($result) {
				$record = [];
				$rowsCount = pg_num_rows(
					$result
				);
				if ($rowsCount === 1) {
					$record = pg_fetch_assoc(
						$result
					);
				}
				pg_free_result(
					$result
				);
				return $record;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return false;
	}

	/**
	 * Execute Sql
	 * 
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 * 
	 * @return bool
	 */
	private function execSql(
		$sql,
		$paramArray
	): bool {
		try {
			$result = pg_query_params(
				$this->sessionServerObject,
				$sql,
				$paramArray
			);
			if ($result) {
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
