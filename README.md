# Session Handlers
Collection of Mostly used Session Handlers

- Supports File / MySql / PostgreSql / MongoDb / Redis / Memcached / Cookie based Session Handlers
- Supports Readonly mode as well for all the above mentioned Session Handlers

## Example

Using Normal session
```PHP
<?php

// For Mongo DB (composer require mongodb/mongodb)
// require __DIR__ . '/vendor/autoload.php';

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Object
$sessionObject = new Session('.env');

// Initialize Session Handler
$sessionObject->initSessionHandler();

// Start session in normal (read/write) mode.
$sessionObject->startReadWrite();
$_SESSION['id'] = rand();

```

Using Read-only mode
```PHP
<?php

// For Mongo DB (composer require mongodb/mongodb)
// require __DIR__ . '/vendor/autoload.php';

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Object
$sessionObject = new Session('.env');

// Initialize Session Handler
$sessionObject->initSessionHandler();

// Start session in readonly mode
// Use when user is already logged in and we need to authorize the client cookie.
$sessionObject->startReadonly();

if (isset($_SESSION)) {
	print_r($_SESSION);
}

```

Using Read-only with Normal session
```PHP
<?php

// For Mongo DB (composer require mongodb/mongodb)
// require __DIR__ . '/vendor/autoload.php';

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Object
$sessionObject = new Session('.env');

// Initialize Session Handler
$sessionObject->initSessionHandler();

// Start session in readonly mode
// Use when user is already logged in and we need to authorize the client cookie.
$sessionObject->startReadonly();

// Auth Check
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
	die('Unauthorized');
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
$sessionObject->startReadWrite();

// Starting use of session in normal code from here
$_SESSION['id'] = rand();

```

Switching from previous session to this package based session handler
```PHP
<?php

// For Mongo DB (composer require mongodb/mongodb)
// require __DIR__ . '/vendor/autoload.php';

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Starting below to switch the session mode with current package.
// Turn on output buffering
ob_start();

// Session Object
$sessionObject = new Session('.env');

// Initialize Session Handler
$sessionObject->initSessionHandler();

$startSwitchTimestamp = strtotime("2026-01-01 00:00:00");
$switchRemainingTimestamp = $startSwitchTimestamp + $sessionObject->ENV->SESSION_LIFETIME

$prevSessionData = [];

if (
	isset($_COOKIE['PrevSessCookieName'])
	&& $switchRemainingTimestamp >= $sessionObject->ENV->timestamp
) {
	// Load session the was it was used previously in read_and_close mode
	// This will load previous session data in $_SESSION
	session_start(['read_and_close' => true]);

	// Collect previous session data
	if (!empty($_SESSION)) {
		$prevSessionData = $_SESSION;
	}

	// Destroy previous session (Note: $_SESSION data will be preserved)
	session_destroy();
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
$sessionObject->startReadWrite();

if (!empty($prevSessionData)) {
	$_SESSION = $prevSessionData;
}

// Auth Check with $_SESSION data.
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
	die('Unauthorized');
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
$sessionObject->startReadWrite();

if (!empty($prevSessionData)) {
	$_SESSION = $prevSessionData;
}

// Starting use of session in normal code from here
$_SESSION['id'] = rand();

// PHP Code
```

Switching between session mode using this session handler package

```PHP
<?php

// For Mongo DB (composer require mongodb/mongodb)
// require __DIR__ . '/vendor/autoload.php';

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Starting below to switch the session mode with current package.
// Turn on output buffering
ob_start();

$prevSessionData = [];

// Session Object
$sessionObject = new Session('.env');

$sessionObject->ENV->SESSION_COOKIE_NAME = 'PHPSESSID';

$startSwitchTimestamp = strtotime("2026-01-01 00:00:00");
$switchRemainingTimestamp = $startSwitchTimestamp + $sessionObject->ENV->SESSION_LIFETIME

if (
	isset($_COOKIE[$sessionObject->ENV->SESSION_COOKIE_NAME])
	&& $switchRemainingTimestamp >= $sessionObject->ENV->timestamp
) {
	$sessionObject->initSessionHandler();
	$sessionObject->startReadonly();

	// Collect previous session data
	if (!empty($_SESSION)) {
		$prevSessionData = $_SESSION;
	}

	// Destroy previous session (Note: $_SESSION data will be preserved)
	session_destroy();
}

// To switch session to MySQL - setting details
$sessionObject->ENV->SESSION_STORE_MODE='MySql';
$sessionObject->ENV->SESSION_MYSQL_HOST = 'localhost';
$sessionObject->ENV->SESSION_MYSQL_PORT = 3306;
$sessionObject->ENV->SESSION_MYSQL_USER = 'root';
$sessionObject->ENV->SESSION_MYSQL_PASSWORD = 'shames11';
$sessionObject->ENV->SESSION_MYSQL_DB = 'db_session';
$sessionObject->ENV->SESSION_MYSQL_TABLE = 'sessions';
$sessionObject->ENV->SESSION_COOKIE_NAME = 'PHPSESSID_New';

// Initialize Session Handler
$sessionObject->initSessionHandler();
$sessionObject->startReadonly();

if (!empty($prevSessionData)) {
	$_SESSION = $prevSessionData;
}

// Auth Check with $_SESSION data.
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
	die('Unauthorized');
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
$sessionObject->startReadWrite();

if (!empty($prevSessionData)) {
	$_SESSION = $prevSessionData;
}

// Starting use of session in normal code from here
$_SESSION['id'] = rand();

// PHP Code
```

Delete user sessions (E.g. used in feature like logout all sessions)

```PHP
<?php

// For Mongo DB (composer require mongodb/mongodb)
// require __DIR__ . '/vendor/autoload.php';

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Session Object
$sessionObject = new Session('.env');

// Initialize Session Handler
$sessionObject->initSessionHandler();

// Delete single session
$sessionObject->deleteSession($sessionId);

// Delete sessions in array
$sessionObject->deleteSessions($sessionIds);

// PHP Code
```

## Database Table for MySql

```SQL
CREATE TABLE IF NOT EXISTS `sessions` (
    `session_id` CHAR(64) NOT NULL,
    `last_accessed` INT UNSIGNED NOT NULL,
    `session_data` MEDIUMBLOB,
    PRIMARY KEY (`session_id`)
) ENGINE=InnoDB;
```

## Database Table for PostgreSql

```SQL
CREATE TABLE IF NOT EXISTS sessions (
	session_id CHAR(64) NOT NULL,
	last_accessed BIGINT NOT NULL,
	session_data TEXT,
	PRIMARY KEY (session_id)
);
```

## 🤝 Contributing

Issues and feature requests are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Session-Handlers/issues)

## Author

👤 **Ramesh N. Jangid (Sharma)**

- Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](LICENSE) licensed.
