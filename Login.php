<?php
include __DIR__ . '/CustomSessionHandler/Session.php';

// Turn on output buffering
ob_start();        

// Initialise Session Handler
Session::initSessionHandler('File');

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if (verifyCredentials($username, $password)) {
        $userDetails = getFromDB($username);
        
        // Start session in normal (read/write) mode.
        Session::start_rw_mode();
        $_SESSION = $userDetails;

        heaeder('Location: dashboard.php');
    }
}
