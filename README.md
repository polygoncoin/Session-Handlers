# Session Handlers
Collection of Mostly used Session Handlers

- Supports File / MySql / MongoDb / Redis / Memcached / Cookie based Session Handlers
- Supports Readonly mode as well for all the above mentioned Session Handlers

## Example

Using Normal session
```PHP
<?php

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'File', $options);
// Session::initSessionHandler(sessionMode: 'MySql');
// Session::initSessionHandler(sessionMode: 'MongoDb');
// Session::initSessionHandler(sessionMode: 'Redis');
// Session::initSessionHandler(sessionMode: 'Memcached');
// Session::initSessionHandler(sessionMode: 'Cookie');

// Start session in normal (read/write) mode.
Session::sessionStartReadWrite();
$_SESSION['id'] = rand();

```

Using Read-only mode
```PHP
<?php

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'File', $options);
// Session::initSessionHandler(sessionMode: 'MySql');
// Session::initSessionHandler(sessionMode: 'MongoDb');
// Session::initSessionHandler(sessionMode: 'Redis');
// Session::initSessionHandler(sessionMode: 'Memcached');
// Session::initSessionHandler(sessionMode: 'Cookie');

// Start session in readonly mode
// Use when user is already logged in and we need to authorize the client cookie.
Session::sessionStartReadonly();

if (isset($_SESSION)) {
    print_r($_SESSION);
}

```

Using Read-only with Normal session
```PHP
<?php

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'File', $options);
// Session::initSessionHandler(sessionMode: 'MySql');
// Session::initSessionHandler(sessionMode: 'MongoDb');
// Session::initSessionHandler(sessionMode: 'Redis');
// Session::initSessionHandler(sessionMode: 'Memcached');
// Session::initSessionHandler(sessionMode: 'Cookie');

// Start session in readonly mode
// Use when user is already logged in and we need to authorize the client cookie.
Session::sessionStartReadonly();

// Auth Check
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
    die('Unauthorized');
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
Session::sessionStartReadWrite();

// Starting use of session in normal code from here
$_SESSION['id'] = rand();

```

Switching from previous session to this package based session handler
```PHP
<?php

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

$prevSessionData = [];

if (isset($_COOKIE['PrevSessCookieName'])) {
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

// Starting below to switch the session mode with current package.
// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'File', $options);
// Session::initSessionHandler(sessionMode: 'MySql');
// Session::initSessionHandler(sessionMode: 'MongoDb');
// Session::initSessionHandler(sessionMode: 'Redis');
// Session::initSessionHandler(sessionMode: 'Memcached');
// Session::initSessionHandler(sessionMode: 'Cookie');

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
Session::sessionStartReadWrite();

if (!empty($prevSessionData)) {
    $_SESSION = $prevSessionData;
}

// Auth Check with $_SESSION data.
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
    die('Unauthorized');
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
Session::sessionStartReadWrite();

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

include_once __DIR__ . '/AutoloadSessionHandler.php';

use CustomSessionHandler\Session;

// Starting below to switch the session mode with current package.
// Turn on output buffering
ob_start();

$prevSessionData = [];

Session::$sessionName = 'PHPSESSID';

if (isset($_COOKIE[Session::$sessionName])) {
    Session::initSessionHandler(sessionMode: 'File');
    Session::sessionStartReadonly();

    // Collect previous session data
    if (!empty($_SESSION)) {
        $prevSessionData = $_SESSION;
    }

    // Destroy previous session (Note: $_SESSION data will be preserved)
    session_destroy();
}

// To switch session to MySQL - setting details
Session::$MYSQL_HOSTNAME = 'localhost';
Session::$MYSQL_PORT = 3306;
Session::$MYSQL_USERNAME = 'root';
Session::$MYSQL_PASSWORD = 'shames11';
Session::$MYSQL_DATABASE = 'db_session';
Session::$MYSQL_TABLE = 'sessions';
Session::$sessionName = 'PHPSESSID_New';

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'MySql');
Session::sessionStartReadonly();

if (!empty($prevSessionData)) {
    $_SESSION = $prevSessionData;
}

// Auth Check with $_SESSION data.
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
    die('Unauthorized');
}

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
Session::sessionStartReadWrite();

if (!empty($prevSessionData)) {
    $_SESSION = $prevSessionData;
}

// Starting use of session in normal code from here
$_SESSION['id'] = rand();

// PHP Code
```

## Database Table for MySql

```SQL
CREATE TABLE IF NOT EXISTS `sessions` (
    `sessionId` CHAR(64) NOT NULL,
    `lastAccessed` INT UNSIGNED NOT NULL,
    `sessionData` MEDIUMBLOB,
    PRIMARY KEY (`sessionId`)
) ENGINE=InnoDB;
```
