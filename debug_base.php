<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "REALPATH DOCUMENT_ROOT: " . realpath($_SERVER['DOCUMENT_ROOT']) . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "REALPATH __DIR__: " . realpath(__DIR__) . "\n";
echo "BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'UNDEFINED') . "\n";
echo "url(): " . url() . "\n";
