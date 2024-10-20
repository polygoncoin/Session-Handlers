<?php
include __DIR__ . '/SessionHandlers/Session.php';

// Initialise Session Handler
Session::initSessionHandler('File');
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

// Auth Check
// if (!isset($_SESSION) || !isset($_SESSION['id'])) {
//     die('Unauthorised');
// }

// Start session in normal (read/write) mode.
// Use once client is authorised and want to make changes in $_SESSION
Session::start_rw_mode();
$_SESSION['id'] = rand();
