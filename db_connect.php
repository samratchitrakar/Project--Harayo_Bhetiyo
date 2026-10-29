<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

$host     = "localhost";
$db       = "harayo_bhetiyo";
$user     = "root";
$password = "";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Database connection failed. Please try again later.");
}

mysqli_set_charset($conn, "utf8");

