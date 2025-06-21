# Session Handlers
Collection of Mostly used Session Handlers

- Supports File / MySql / Redis / Memcached / Cookie based Session Handlers
- Supports Readonly mode as well for all the above mentioned Session Handlers

## Example

Using Normal session
```PHP
<?php
include __DIR__ . '/CustomSessionHandler/Session.php';

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialise Session Handler
Session::initSessionHandler('File', $options);
// Session::initSessionHandler('MySql');
// Session::initSessionHandler('Redis');
// Session::initSessionHandler('Memcached');
// Session::initSessionHandler('Cookie');

// Start session in normal (read/write) mode.
Session::start_rw_mode();
$_SESSION['id'] = rand();

```

Using Read-only mode
```PHP
<?php
include __DIR__ . '/CustomSessionHandler/Session.php';

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialise Session Handler
Session::initSessionHandler('File', $options);
// Session::initSessionHandler('MySql');
// Session::initSessionHandler('Redis');
// Session::initSessionHandler('Memcached');
// Session::initSessionHandler('Cookie');

// Start session in readonly mode
// Use when user is already logged in and we need to authorise the client cookie.
Session::start_readonly();

if (isset($_SESSION)) {
    print_r($_SESSION);
}

```

Using Read-only with Normal session
```PHP
<?php
include __DIR__ . '/CustomSessionHandler/Session.php';

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialise Session Handler
Session::initSessionHandler('File', $options);
// Session::initSessionHandler('MySql');
// Session::initSessionHandler('Redis');
// Session::initSessionHandler('Memcached');
// Session::initSessionHandler('Cookie');

// Start session in readonly mode
// Use when user is already logged in and we need to authorise the client cookie.
Session::start_readonly();

// Auth Check
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
    die('Unauthorised');
}

// Start session in normal (read/write) mode.
// Use once client is authorised and want to make changes in $_SESSION
Session::start_rw_mode();
$_SESSION['id'] = rand();

```

Switching from previous session to this package based session handler
```PHP
<?php
// Load session the was it was used previously in read_and_close mode
// This will load previous session data in $_SESSION
session_start(['read_and_close' => true]);

// Collect previous session data
$prevSessionData = $_SESSION;
// Destroy previous session (Note: $_SESSION data will be preserved)
session_destroy();

// Auth Check with $_SESSION data.
if (!isset($_SESSION) || !isset($_SESSION['id'])) {
    die('Unauthorised');
}

// Start below to switch the session mode with current package.
include __DIR__ . '/CustomSessionHandler/Session.php';

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialise Session Handler
Session::initSessionHandler('File', $options);
// Session::initSessionHandler('MySql');
// Session::initSessionHandler('Redis');
// Session::initSessionHandler('Memcached');
// Session::initSessionHandler('Cookie');

// Start session in normal (read/write) mode.
// Use once client is authorised and want to make changes in $_SESSION
Session::start_rw_mode();

if (!empty($prevSessionData)) {
    $_SESSION = $prevSessionData;
}

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
