<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Controllers/ResidentController.php';

$controller = new ResidentController();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$response = [
    'success' => false,
    'data'    => null,
    'message' => 'Operación no permitida'
];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $response = $controller->getResident((int)$_GET['id']);
            } elseif (isset($_GET['search'])) {
                $response = $controller->searchResident($_GET['search']);
            } elseif (isset($_GET['status'])) {
                $response = $controller->getResidentsByStatus($_GET['status']);
            } elseif (isset($_GET['count'])) {
                $response = $controller->countResidents();
            } else {
                $response = $controller->listResidents();
            }
            break;

        case 'POST':
            $response = $controller->addResident($input);
            break;

        case 'PUT':
            if (isset($input['id'])) {
                $response = $controller->updateResident((int)$input['id'], $input);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de residente requerido para actualizar'
                ];
            }
            break;

        case 'DELETE':
            if (isset($input['id'])) {
                $response = $controller->removeResident((int)$input['id']);
            } elseif (isset($_GET['id'])) {
                $response = $controller->removeResident((int)$_GET['id']);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de residente requerido para eliminar'
                ];
            }
            break;

        default:
            $response = [
                'success' => false,
                'message' => "Método HTTP {$method} no permitido"
            ];
    }
} catch (Throwable $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
