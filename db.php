<?php

$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'blog_site';
$dbUser = 'root';
$dbPass = '';

$pdo = new PDO(
    "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
    $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
