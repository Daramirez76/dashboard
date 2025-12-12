<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/User.php';

class ForgotPasswordController
{
    /** @var User */
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Genera un token de recuperación de contraseña.
     *
     * @param string $email
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function requestPasswordRecovery(string $email): array
    {
        try {
            // Validar que el email no esté vacío
            if (empty($email)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => 'Email es requerido'
                ];
            }

            // Verificar si el email existe
            [$exists, $result] = $this->userModel->get_user_by_email($email);

            if (!$exists) {
                // Por seguridad, no revelar si el email existe o no
                return [
                    'success' => true,
                    'data'    => null,
                    'message' => 'Si el email existe, recibirá instrucciones de recuperación'
                ];
            }

            // Generar token
            $token = $this->generateToken(32);

            // Calcular expiración (24 horas)
            $expiration = (new DateTime())->add(new DateInterval('PT24H'));

            /**
             * AQUÍ VIENE LA PARTE IMPORTANTE:
             * Debes guardar el token en base de datos.
             * Puedes crear un método en tu modelo User o un modelo PasswordReset:
             *
             * $this->userModel->save_recovery_token(
             *     $result['id'],
             *     $email,
             *     $token,
             *     $expiration
             * );
             *
             * Para no forzar una implementación concreta, lo dejo comentado.
             */

            return [
                'success' => true,
                'data'    => [
                    'token'      => $token,
                    'email'      => $email,
                    'expiration' => $expiration->format('Y-m-d H:i:s')
                ],
                'message' => 'Token de recuperación generado'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Error en la recuperación de contraseña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reinicia la contraseña de un usuario.
     *
     * @param string $email
     * @param string $newPassword
     * @param string $passwordConfirm
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword(
        string $email,
        string $newPassword,
        string $passwordConfirm
    ): array {
        try {
            // Validaciones
            if (empty($email) || empty($newPassword) || empty($passwordConfirm)) {
                return [
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ];
            }

            if ($newPassword !== $passwordConfirm) {
                return [
                    'success' => false,
                    'message' => 'Las contraseñas no coinciden'
                ];
            }

            // Actualizar contraseña (modelo debe devolver [bool $success, string $message])
            [$success, $message] = $this->userModel->update_password($email, $newPassword);

            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar contraseña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si un token es válido.
     *
     * Ojo: En PHP debe ir contra la BD, NO en memoria.
     *
     * @param string $token
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function verifyToken(string $token): array
    {
        try {
            if (empty($token)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => 'Token inválido'
                ];
            }

            /**
             * Aquí deberías consultar la BD. Ejemplo conceptual:
             *
             * $tokenData = $this->userModel->get_recovery_token($token);
             *
             * if (!$tokenData) {
             *     return ['success' => false, 'data' => null, 'message' => 'Token inválido'];
             * }
             *
             * $now = new DateTime();
             * $expiration = new DateTime($tokenData['expiration']);
             *
             * if ($now > $expiration) {
             *     // Opcional: eliminar token de la BD
             *     // $this->userModel->delete_recovery_token($token);
             *     return ['success' => false, 'data' => null, 'message' => 'Token expirado'];
             * }
             *
             * return ['success' => true, 'data' => $tokenData, 'message' => 'Token válido'];
             */

            // De momento, implementación stub para que no rompa:
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Validación de token no implementada aún en PHP'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Error al verificar token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida el formato del email usando el modelo.
     *
     * @param string $email
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateEmailFormat(string $email): array
    {
        $isValid = $this->userModel->validate_email($email);

        return [
            'success' => (bool) $isValid,
            'message' => $isValid ? 'Email válido' : 'Formato de email inválido'
        ];
    }

    /**
     * Valida el formato de la contraseña usando el modelo.
     *
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function validatePasswordFormat(string $password): array
    {
        // En Python devolvías (is_valid, message) :contentReference[oaicite:4]{index=4}
        [$isValid, $message] = $this->userModel->validate_password($password);

        return [
            'success' => (bool) $isValid,
            'message' => $message
        ];
    }

    /**
     * Genera un token aleatorio.
     *
     * @param int $length
     * @return string
     */
    private function generateToken(int $length = 32): string
    {
        // Generamos bytes aleatorios y los convertimos a hex
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }
}
