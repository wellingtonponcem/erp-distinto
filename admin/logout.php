<?php
session_start();
session_destroy();
require_once dirname(__DIR__) . '/includes/db.php';
header('Location: ' . url('admin/login.php'));
exit;
