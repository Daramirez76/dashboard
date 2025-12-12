<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/User.php';

class LoginController
{
    /** @var User */
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Inicia sesión de un usuario.
     *
     * @param string $usuario
     * @param string $password
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function login(string $usuario, string $password): array
    {
        try {
            // Validar que los campos no estén vacíos
            if (empty($usuario) || empty($password)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => 'Usuario y contraseña son requeridos'
                ];
            }

            // Verificar credenciales (el modelo debe devolver [bool $success, mixed $result])
            [$success, $result] = $this->userModel->verify_user($usuario, $password);

            if ($success) {
                return [
                    'success' => true,
                    'data'    => [
                        'usuario_id' => $result['id'],
                        'usuario'    => $result['usuario'],
                        'email'      => $result['email'],
                    ],
                    'message' => 'Login exitoso'
                ];
            }

            return [
                'success' => false,
                'data'    => null,
                'message' => is_string($result) ? $result : 'Credenciales inválidas'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Error en el login: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida que usuario y contraseña no estén vacíos.
     *
     * @param string $usuario
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateCredentials(string $usuario, string $password): array
    {
        if (empty($usuario) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Usuario y contraseña son requeridos'
            ];
        }

        return [
            'success' => true,
            'message' => 'Credenciales válidas'
        ];
    }
}
