<?php
require_once 'config/database.php';

header('Content-Type: application/json');
echo json_encode(['csrf_token' => csrfToken()]);
