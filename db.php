<?php

define('HOST', 'localhost');
define('USER', 'root');
define('PASSWORD', '');
define('DATABASE', 'php-auth-test');

define('DIRROOT', dirname(__FILE__));

$conn = new mysqli(HOST, USER, PASSWORD, DATABASE);
if ($conn->connect_errno) {
    echo "Failed to connect to MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error;
}

require 'auth.php';

//On whatever page you'd like password Protected just pass the $conn variable to the auth class, like so:
$auth = new auth($conn);


	



