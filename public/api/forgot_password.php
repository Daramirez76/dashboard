<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Controllers/ForgotPasswordController.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$email = $input['email'] ?? '';

$controller = new ForgotPasswordController();
$response   = $controller->requestPasswordRecovery($email);

echo json_encode($response);
