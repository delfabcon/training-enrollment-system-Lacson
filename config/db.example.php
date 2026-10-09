<?php
// config/db.example.php
// Copy this file to config/db.php and update with your database credentials
require_once __DIR__ . '/../classes/Database.php';

$host = 'localhost';
$dbname = 'training_db';
$user = 'root';
$pass = ''; // Enter your database password here

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$db = Database::getInstance($dsn, $user, $pass);
