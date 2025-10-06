<?php

require_once __DIR__ . '/AutoloadSessionHandler.php'; // phpcs:ignore

use CustomSessionHandler\Session;

// Turn on output buffering
ob_start();

// Initialize Session Handler
Session::initSessionHandler(sessionMode: 'File');

if (isset($POST['submit'])) {
    $username = $POST['username'];
    $password = $POST['password'];
    if (verifyCredentials($username, $password)) { // phpcs:ignore
        $userDetails = getFromDB($username);

        // Start session in normal (read/write) mode.
        Session::sessionStartReadWrite();
        $_SESSION = $userDetails;

        header(header: 'Location: dashboard.php');
    }
}
