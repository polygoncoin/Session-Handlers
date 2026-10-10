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

use CustomSessionHandler\Containers\SessionContainerInterface;

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  CustomSessionHandler
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class CustomSessionHandler implements
	\SessionHandlerInterface,
	\SessionIdInterface,
	\SessionUpdateTimestampHandlerInterface
{
	/**
	 * Session Containers
	 * 
	 * @var null|SessionContainerInterface
	 */
	private $container = null;

	/**
	 * Session cookie name
	 * 
	 * @var null|string
	 */
	public $sessionName = null;

	/**
	 * Session id
	 * 
	 * @var string
	 */
	private $sessionId = '';

	/**
	 * Session Data
	 * 
	 * @var null|string
	 */
	private $sessionData = '';

	/**
	 * Session found
	 * 
	 * @var null|bool
	 */
	private $foundSession = null;

	/**
	 * Session id created flag to handle session_regenerate_id
	 * In this case validateId is called after create_sid function
	 * Also, we have used this to validate created sessionId
	 * 
	 * @var null|bool
	 */
	private $creatingSessionId = null;

	/**
	 * _isTimestampUpdated flag for read_and_close or readonly session behaviour
	 * To be careful with the 'read_and_close' option
	 * It doesn't update the session last modification timestamp
	 * unlike the default PHP behaviour
	 * 
	 * @var bool
	 */
	private $isTimestampUpdated = false;

	/**
	 * Constructor
	 * 
	 * @param SessionContainerInterface $container Container
	 */
	public function __construct(
		&$container
	) {
		$this->container = &$container;
	}

	/**
	 * Open session
	 * A callable with the following signature
	 * 
	 * @param string $sessionSavePath Save Path
	 * @param string $sessionName     Session Name
	 * 
	 * @return bool true for success or false for failure
	 */
	public function open(
		$sessionSavePath,
		$sessionName
	): bool {
		$this->sessionName = $sessionName;
		$this->container->init(
			sessionSavePath: $sessionSavePath,
			sessionName: $this->sessionName
		);

		return true;
	}

	/**
	 * Validate session id
	 * Calls if session cookie is present in request
	 * 
	 * A callable with the following signature
	 * 
	 * @param string $sessionId Session id
	 * 
	 * @return bool true if the session id is valid otherwise false
	 */
	public function validateId(
		$sessionId
	): bool {
		// Don't change this return value
		return $this->foundSessionId(
			sessionId: $sessionId
		);
	}

	/**
	 * Found session id
	 * 
	 * @param string $sessionId Session id
	 * 
	 * @return bool true if the session id is valid otherwise false
	 */
	public function foundSessionId(
		$sessionId
	): bool {
		$sessionData = $this->container->getSession(
			sessionId: $sessionId
		);
		if ($sessionData !== false) {
			$foundSession = true;
		} else {
			$foundSession = false;
		}

		if ($this->creatingSessionId === true) {
			$foundSession = !$foundSession;
			if ($foundSession === true) {
				$this->creatingSessionId = false;
			}
		} else {
			$this->foundSession = $foundSession;
			if ($foundSession) {
				$this->sessionData = $sessionData;
			}
		}

		// Don't change this return value
		return $foundSession;
	}

	/**
	 * Create session id
	 * 
	 * Calls if no session cookie is present
	 * Invoked internally when a new session id is needed
	 * 
	 * A callable with the following signature
	 * 
	 * @return string should be new session id
	 */
	public function create_sid(): string // phpcs:ignore
	{
		$this->creatingSessionId = true;

		for (;true;) {
			$sessionId = $this->getRandomString();
			if (
				$this->foundSessionId(
					sessionId: $sessionId
				) === true
			) {
				break;
			}
		};

		return $sessionId;
	}

	/**
	 * Read session data
	 * 
	 * A callable with the following signature
	 * 
	 * @param string $sessionId Session id
	 * 
	 * @return string|false the session data or an empty string
	 */
	public function read(
		$sessionId
	): string|false {
		$this->sessionId = $sessionId;
		return $this->sessionData;
	}

	/**
	 * Write session data
	 * 
	 * When session.lazy_write is enabled, and session data is unchanged
	 * it will skip this method call. Instead it will call updateTimestamp
	 * 
	 * A callable with the following signature
	 * 
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 * 
	 * @return bool true for success or false for failure
	 */
	public function write(
		$sessionId,
		$sessionData
	): bool {
		$this->sessionData = $sessionData;
		// Won't allow creating empty entries
		// unless previous data is not empty
		if (
			empty($sessionData)
			|| empty(
				unserialize(
					data: $sessionData
				)
			)
		) {
			if ($this->foundSession) {
				$this->destroy(
					$sessionId
				);
			}
			$this->unsetSessionCookie();
			return true;
		}

		$function = ($this->foundSession) ? 'updateSession' : 'setSession';
		if (
			$this->container->$function(
				sessionId: $sessionId,
				sessionData: $sessionData
			)
		) {
			$this->isTimestampUpdated = true;
		}

		return $this->isTimestampUpdated;
	}

	/**
	 * Update session timestamp
	 * 
	 * When session.lazy_write is enabled, and session data is unchanged
	 * UpdateTimestamp is called instead (of write) to only update the timestamp
	 * of session
	 * 
	 * A callable with the following signature
	 * 
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 * 
	 * @return bool true for success or false for failure
	 */
	public function updateTimestamp(
		$sessionId,
		$sessionData
	): bool {
		$this->sessionData = $sessionData;
		// Won't allow updating empty entries when session.lazy_write is enabled
		// unless previous data is not empty
		if (
			empty($sessionData)
			|| empty(
				unserialize(
					data: $sessionData
				)
			)
		) {
			$this->destroy(
				$sessionId
			);
			return true;
		}

		if (
			$this->container->touchSession(
				sessionId: $sessionId,
				sessionData: $sessionData
			)
		) {
			$this->isTimestampUpdated = true;
		}

		return $this->isTimestampUpdated;
	}

	/**
	 * Cleanup old sessions
	 * 
	 * A callable with the following signature
	 * 
	 * @param integer $sessionMaxLifetime Session life time
	 * 
	 * @return bool true for success or false for failure
	 */
	public function gc(
		$sessionMaxLifetime
	): int|false {
		return $this->container->gcSession(
			sessionMaxLifetime: $sessionMaxLifetime
		);
	}

	/**
	 * Destroy a session
	 * 
	 * A callable with the following signature
	 * 
	 * @param string $sessionId Session id
	 * 
	 * @return bool true for success or false for failure
	 */
	public function destroy(
		$sessionId
	): bool {
		// Deleting session cookies set on customer end
		$this->unsetSessionCookie();

		return $this->container->deleteSession(
			sessionId: $sessionId
		);
	}

	/**
	 * Close the session
	 * 
	 * A callable with the following signature
	 * 
	 * @return bool true for success or false for failure
	 */
	public function close(): bool
	{
		// Updating timestamp for readonly mode (read_and_close option)
		if (
			!$this->isTimestampUpdated
			&& $this->foundSession === true
		) {
			$this->container->touchSession(
				sessionId: $this->sessionId,
				sessionData: $this->sessionData
			);
		}

		// $this->checkCookiesHeader();

		$this->container->closeSession();
		$this->sessionData = '';
		$this->foundSession = null;
		$this->isTimestampUpdated = false;

		return true;
	}

	/**
	 * Returns 64 char random string
	 * 
	 * @return string
	 */
	private function getRandomString(): string
	{
		return bin2hex(
			string: random_bytes(
				length: 32
			)
		);
	}

	/**
	 * Unset session cookies
	 * 
	 * @return void
	 */
	private function unsetSessionCookie(): void
	{
		if (!empty($_COOKIE[$this->sessionName])) {
			setcookie(
				name: $this->sessionName,
				value: '',
				expires_or_options: 1,
				path: $this->container->sessionOptions['cookie_path']
			);
		}
		if (!empty($_COOKIE[$this->container->ENV->SESSION_DATA_COOKIE_NAME])) {
			setcookie(
				name: $this->container->ENV->SESSION_DATA_COOKIE_NAME,
				value: '',
				expires_or_options: 1,
				path: $this->container->sessionOptions['cookie_path']
			);
		}
	}

	/**
	 * Check Cookies Header
	 * 
	 * @return void
	 */
	private function checkCookiesHeader(): void
	{
		// Check header is sent.
		if (headers_sent()) {
			return;
		}

		// Removed Session Cookie if read_and_close is enabled
		if (
			isset($this->container->sessionOptions['read_and_close'])
			&& $this->container->sessionOptions['read_and_close'] === true
			&& $this->creatingSessionId === true
		) {
			// Remove Session Set-Cookie header
			$headerArray = headers_list();
			$headerFound = false;
			foreach ($headerArray as $index => $header) {
				if (
					strpos(
						haystack: $header,
						needle: $this->sessionName
					) !== false
				) {
					unset($headerArray[$index]);
					$headerFound = true;
					break;
				}
			}
			if ($headerFound) {
				header_remove();
				foreach ($headerArray as &$header) {
					header(
						header: $header
					);
				}
			}
		}
	}
}
