<?php
include_once __DIR__ . '/Autoload.php';

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Session Runtime Configuration
$options = [];

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'File');
// Session::initSessionHandler(sessionMode: 'MySql');
// Session::initSessionHandler(sessionMode: 'Redis');
// Session::initSessionHandler(sessionMode: 'Memcached');
// Session::initSessionHandler(sessionMode: 'Cookie');

// Start session in readonly mode
// Use when user is already logged in and we need to authorize the client cookie.
Session::sessionStartReadonly();

if (isset($_SESSION)) {
    print_r(value: $_SESSION);
}

// Auth Check
// if (!isset($_SESSION) || !isset($_SESSION['id'])) {
//     die('Unauthorized');
// }

// Start session in normal (read/write) mode.
// Use once client is authorized and want to make changes in $_SESSION
Session::sessionStartReadWrite();
$_SESSION['id'] = rand();
