<?php
include __DIR__ . '/SessionHandlers/Session.php';

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if (verifyCredentials($username, $password)) {
        $userDetails = getFromDB($username);
        
        // Initialise Session Handler
        Session::initSessionHandler('File');

        // Start session in normal (read/write) mode.
        Session::start_rw_mode();
        $_SESSION = $userDetails;

        heaeder('Location: dashboard.php');
    }
}
