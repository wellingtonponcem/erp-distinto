<?php
header('Content-Type: application/json');
echo json_encode(['status' => 'pong', 'time' => date('Y-m-d H:i:s')]);
