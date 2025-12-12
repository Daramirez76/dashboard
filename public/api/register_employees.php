<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Controllers/RegistroController.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

// Mapea EXACTO a lo que pide tu HTML (ids):
// nombre, apellido, tipoDoc, numDoc, direccion, telefono, correo, cargo, usuario, contrasena
$data = [
  'nombre'     => (string)($input['nombre'] ?? ''),
  'apellido'   => (string)($input['apellido'] ?? ''),
  'tipoDoc'    => (string)($input['tipoDoc'] ?? ''),
  'numDoc'     => (string)($input['numDoc'] ?? ''),
  'direccion'  => (string)($input['direccion'] ?? ''),
  'telefono'   => (string)($input['telefono'] ?? ''),
  'correo'     => (string)($input['correo'] ?? ''),
  'cargo'      => (string)($input['cargo'] ?? ''),
  'usuario'    => (string)($input['usuario'] ?? ''),
  'contrasena' => (string)($input['contrasena'] ?? ''),
];

$controller = new RegistroController();
echo json_encode($controller->registerEmployee($data));
