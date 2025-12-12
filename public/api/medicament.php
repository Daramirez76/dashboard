<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Controllers/MedicamentController.php';

$controller = new MedicamentController();
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
                $response = $controller->getMedicament((int)$_GET['id']);
            } elseif (isset($_GET['resident_id'])) {
                $response = $controller->getMedicamentsByResident((int)$_GET['resident_id']);
            } elseif (isset($_GET['stock'])) {
                $response = $controller->checkStock((int)$_GET['stock']);
            } elseif (isset($_GET['low_stock'])) {
                $response = $controller->getLowStockMedicaments();
            } elseif (isset($_GET['count'])) {
                $response = $controller->countMedicaments();
            } else {
                $response = $controller->listMedicaments();
            }
            break;

        case 'POST':
            $response = $controller->addMedicament($input);
            break;

        case 'PUT':
            if (isset($input['id'])) {
                $response = $controller->updateMedicament((int)$input['id'], $input);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de medicamento requerido para actualizar'
                ];
            }
            break;

        case 'DELETE':
            if (isset($input['id'])) {
                $response = $controller->removeMedicament((int)$input['id']);
            } elseif (isset($_GET['id'])) {
                $response = $controller->removeMedicament((int)$_GET['id']);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de medicamento requerido para eliminar'
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
