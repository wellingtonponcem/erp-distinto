<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$db = Database::get();
$emails = ['faustinosdg@gmail.com', 'jeaneponcem13@gmail.com'];
$results = [];

foreach ($emails as $email) {
    $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $results[] = $stmt->fetch(PDO::FETCH_ASSOC);
}

responderJson($results);
