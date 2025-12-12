<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Controllers/LoginController.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$usuario  = $input['usuario']  ?? '';
$password = $input['password'] ?? '';

$controller = new LoginController();
$response   = $controller->login($usuario, $password);

echo json_encode($response);
