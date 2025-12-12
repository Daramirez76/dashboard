<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/User.php';

class RegistroController
{
    /** @var User */
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Registra un nuevo usuario.
     *
     * @param string $usuario
     * @param string $email
     * @param string $password
     * @param string $passwordConfirm
     * @return array ['success' => bool, 'message' => string]
     */
    public function register(
        string $usuario,
        string $email,
        string $password,
        string $passwordConfirm
    ): array {
        try {
            // Validar que los campos no estén vacíos
            if (empty($usuario) || empty($email) || empty($password) || empty($passwordConfirm)) {
                return [
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ];
            }

            // Validar que las contraseñas coincidan
            if ($password !== $passwordConfirm) {
                return [
                    'success' => false,
                    'message' => 'Las contraseñas no coinciden'
                ];
            }

            // Registrar usuario (modelo debe devolver [bool $success, string $message])
            [$success, $message] = $this->userModel->register_user($usuario, $email, $password);

            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error en el registro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si un usuario ya existe.
     *
     * @param string $usuario
     * @return array ['success' => bool, 'exists' => bool]
     */
    public function checkUsuarioExists(string $usuario): array
    {
        $exists = $this->userModel->user_exists($usuario);
        return [
            'success' => true,
            'exists'  => (bool) $exists
        ];
    }

    /**
     * Verifica si un email ya está registrado.
     *
     * @param string $email
     * @return array ['success' => bool, 'exists' => bool]
     */
    public function checkEmailExists(string $email): array
    {
        [$success, $user] = $this->userModel->get_user_by_email($email);

        return [
            'success' => true,
            'exists'  => (bool) $success
        ];
    }

    public function registerEmployee(array $data): array
    {
        try {
            [$success, $message] = $this->userModel->register_employee($data);
            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error en el registro del empleado: ' . $e->getMessage()
            ];
        }
    }

}
