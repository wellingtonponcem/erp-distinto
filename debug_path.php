<?php
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "realpath(DOCUMENT_ROOT): " . realpath($_SERVER['DOCUMENT_ROOT']) . "<br>";
echo "realpath(__DIR__): " . realpath(__DIR__) . "<br>";

$doc_root = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
$app_root = rtrim(str_replace('\\', '/', realpath(__DIR__)), '/');
$base = str_replace($doc_root, '', $app_root);
echo "BASE_PATH detected: '" . $base . "'<br>";
?>
