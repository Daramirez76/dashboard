<?php
declare(strict_types=1);

class User
{
    /** @var PDO */
    private $conn;

    public function __construct()
    {
        $this->initDb();
    }

    /**
     * Conexión PDO a MySQL (TCP).
     */
    private function getConnection(): PDO
    {
        if (!isset($this->conn)) {
            $host = '127.0.0.1';
            $port = 3306;
            $db   = 'hga';
            $user = 'appuser';
            $pass = 'AppPass123!';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

            $this->conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return $this->conn;
    }

    /**
     * Asegura estructura mínima:
     * - usuario tenga PK autoincremental (id)
     * - employees exista y referencie usuario(id)
     */
    private function initDb(): void
    {
        try {
            $conn = $this->getConnection();

            // 1) Garantizar PK en `usuario` (si ya existe, no hace nada).
            $hasId = (int)$conn->query("
                SELECT COUNT(*) AS c
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'usuario'
                  AND COLUMN_NAME = 'id'
            ")->fetchColumn();

            if ($hasId === 0) {
                // Agregar columna id como PK
                $conn->exec("ALTER TABLE usuario ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
            } else {
                // Si existe id pero no es PK (caso raro), intentar asegurar PK
                $isPk = (int)$conn->query("
                    SELECT COUNT(*) AS c
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'usuario'
                      AND CONSTRAINT_NAME = 'PRIMARY'
                      AND COLUMN_NAME = 'id'
                ")->fetchColumn();

                if ($isPk === 0) {
                    // Esto puede fallar si hay otra PK ya definida (no debería en tu dump)
                    // En ese caso, lo logueamos y seguimos.
                    try {
                        $conn->exec("ALTER TABLE usuario ADD PRIMARY KEY (id)");
                    } catch (PDOException $e) {
                        error_log("No se pudo definir PK en usuario(id): " . $e->getMessage());
                    }
                }
            }

            // 2) Crear employees referenciando usuario(id)
            $conn->exec("
                CREATE TABLE IF NOT EXISTS employees (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    usuario_id INT UNSIGNED NOT NULL,
                    nombre VARCHAR(80) NOT NULL,
                    apellido VARCHAR(80) NOT NULL,
                    tipo_doc VARCHAR(30) NOT NULL,
                    num_doc VARCHAR(30) NOT NULL,
                    direccion VARCHAR(150) NOT NULL,
                    telefono VARCHAR(30) NOT NULL,
                    correo VARCHAR(120) NOT NULL,
                    cargo VARCHAR(80) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uk_employees_num_doc (num_doc),
                    KEY idx_employees_usuario_id (usuario_id),
                    CONSTRAINT fk_employees_usuario
                        FOREIGN KEY (usuario_id)
                        REFERENCES usuario(id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            error_log('Error al inicializar DB: ' . $e->getMessage());
        }
    }

    private function hash_password(string $password): string
    {
        return hash('sha256', $password);
    }

    public function validate_email(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validate_username(string $usuario): array
    {
        if (strlen($usuario) < 3 || strlen($usuario) > 20) {
            return [false, 'El usuario debe tener entre 3 y 20 caracteres'];
        }
        if (!ctype_alnum($usuario)) {
            return [false, 'El usuario solo puede contener letras y números'];
        }
        return [true, 'Usuario válido'];
    }

    public function validate_password(string $password): array
    {
        if (strlen($password) < 6) {
            return [false, 'La contraseña debe tener al menos 6 caracteres'];
        }
        return [true, 'Contraseña válida'];
    }

    /**
     * Registro "simple": usuario + email + password
     * Inserta en tabla REAL: `usuario`
     */
    public function register_user(string $usuario, string $email, string $password): array
    {
        try {
            [$isValid, $msg] = $this->validate_username($usuario);
            if (!$isValid) return [false, $msg];

            if (!$this->validate_email($email)) return [false, 'Email inválido'];

            [$isValid, $msg] = $this->validate_password($password);
            if (!$isValid) return [false, $msg];

            $hashedPassword = $this->hash_password($password);

            $conn = $this->getConnection();

            // En tu tabla `usuario` son requeridos: tipo_doc, doc_id, nombre, apellido, direccion, telefono, cod_rol, parentesco
            // Para registro "simple" no los tienes, así que no es coherente con tu esquema.
            // Por eso aquí devolvemos error claro.
            return [false, 'Registro simple no soportado: la tabla usuario exige datos adicionales. Use register_employee().'];

        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                return [false, 'El usuario o email ya está registrado'];
            }
            return [false, 'Error al registrar usuario: ' . $e->getMessage()];
        } catch (Throwable $e) {
            return [false, 'Error al registrar usuario: ' . $e->getMessage()];
        }
    }

    /**
     * ✅ Registro COMPLETO: usa TODOS los campos del HTML:
     * nombre, apellido, tipoDoc, numDoc, direccion, telefono, correo, cargo, usuario, contrasena
     */
    public function register_employee(array $data): array
    {
        $conn = null;

        try {
            $required = ['nombre','apellido','tipoDoc','numDoc','direccion','telefono','correo','cargo','usuario','contrasena'];
            foreach ($required as $k) {
                if (!isset($data[$k]) || trim((string)$data[$k]) === '') {
                    return [false, "Todos los campos son requeridos. Falta: {$k}"];
                }
            }

            $usuario  = trim((string)$data['usuario']);
            $email    = trim((string)$data['correo']);
            $password = (string)$data['contrasena'];

            [$okUser, $msgUser] = $this->validate_username($usuario);
            if (!$okUser) return [false, $msgUser];

            if (!$this->validate_email($email)) return [false, 'Formato de email inválido'];

            [$okPass, $msgPass] = $this->validate_password($password);
            if (!$okPass) return [false, $msgPass];

            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Insert en `usuario` (tabla real)
            $stmtUser = $conn->prepare("
                INSERT INTO usuario
                    (tipo_doc, doc_id, nombre, apellido, direccion, telefono, email, usuario, `contraseña`, cod_rol, parentesco)
                VALUES
                    (:tipo_doc, :doc_id, :nombre, :apellido, :direccion, :telefono, :email, :usuario, :contrasena, :cod_rol, :parentesco)
            ");

            // Define un rol por defecto para empleado (ajusta si tu negocio usa otro)
            $defaultRol = 2; // cuidador/empleado según tu dump

            $stmtUser->execute([
                ':tipo_doc'    => trim((string)$data['tipoDoc']),
                ':doc_id'      => (int)$data['numDoc'],
                ':nombre'      => trim((string)$data['nombre']),
                ':apellido'    => trim((string)$data['apellido']),
                ':direccion'   => trim((string)$data['direccion']),
                ':telefono'    => (int)$data['telefono'],
                ':email'       => $email,
                ':usuario'     => $usuario,
                ':contrasena'  => $this->hash_password($password),
                ':cod_rol'      => $defaultRol,
                ':parentesco'  => '', // en tu dump es NOT NULL; para empleados normalmente vacío
            ]);

            $usuarioId = (int)$conn->lastInsertId();

            // Insert en employees (perfil extendido)
            $stmtEmp = $conn->prepare("
                INSERT INTO employees
                    (usuario_id, nombre, apellido, tipo_doc, num_doc, direccion, telefono, correo, cargo)
                VALUES
                    (:usuario_id, :nombre, :apellido, :tipo_doc, :num_doc, :direccion, :telefono, :correo, :cargo)
            ");
            $stmtEmp->execute([
                ':usuario_id' => $usuarioId,
                ':nombre'     => trim((string)$data['nombre']),
                ':apellido'   => trim((string)$data['apellido']),
                ':tipo_doc'   => trim((string)$data['tipoDoc']),
                ':num_doc'    => trim((string)$data['numDoc']),
                ':direccion'  => trim((string)$data['direccion']),
                ':telefono'   => trim((string)$data['telefono']),
                ':correo'     => $email,
                ':cargo'      => trim((string)$data['cargo']),
            ]);

            $conn->commit();
            return [true, 'Empleado registrado exitosamente'];
        } catch (PDOException $e) {
            if ($conn && $conn->inTransaction()) $conn->rollBack();

            if ((int)$e->getCode() === 23000) {
                return [false, 'Datos duplicados: usuario, email o documento ya existen'];
            }
            return [false, 'Error al registrar empleado: ' . $e->getMessage()];
        } catch (Throwable $e) {
            if ($conn && $conn->inTransaction()) $conn->rollBack();
            return [false, 'Error al registrar empleado: ' . $e->getMessage()];
        }
    }

    /**
     * Login contra tabla REAL `usuario`
     */
    public function verify_user(string $usuario, string $password): array
    {
        try {
            $conn = $this->getConnection();

            $hashedPassword = $this->hash_password($password);

            $stmt = $conn->prepare("
                SELECT id, usuario, email
                FROM usuario
                WHERE usuario = :usuario
                  AND `contraseña` = :contrasena
                LIMIT 1
            ");
            $stmt->execute([
                ':usuario'    => $usuario,
                ':contrasena' => $hashedPassword,
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return [true, [
                    'id'      => (int)$user['id'],
                    'usuario' => $user['usuario'],
                    'email'   => $user['email'],
                ]];
            }

            return [false, 'Usuario o contraseña incorrectos'];
        } catch (Throwable $e) {
            return [false, 'Error al verificar usuario: ' . $e->getMessage()];
        }
    }

    public function get_user_by_email(string $email): array
    {
        try {
            $conn = $this->getConnection();

            $stmt = $conn->prepare("
                SELECT id, usuario, email
                FROM usuario
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return [true, [
                    'id'      => (int)$user['id'],
                    'usuario' => $user['usuario'],
                    'email'   => $user['email'],
                ]];
            }

            return [false, 'Email no registrado'];
        } catch (Throwable $e) {
            return [false, 'Error al buscar usuario: ' . $e->getMessage()];
        }
    }

    public function update_password(string $email, string $newPassword): array
    {
        try {
            [$isValid, $msg] = $this->validate_password($newPassword);
            if (!$isValid) return [false, $msg];

            $hashedPassword = $this->hash_password($newPassword);

            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                UPDATE usuario
                   SET `contraseña` = :contrasena
                 WHERE email = :email
            ");
            $stmt->execute([
                ':contrasena' => $hashedPassword,
                ':email'      => $email,
            ]);

            if ($stmt->rowCount() > 0) return [true, 'Contraseña actualizada exitosamente'];

            return [false, 'Email no encontrado'];
        } catch (Throwable $e) {
            return [false, 'Error al actualizar contraseña: ' . $e->getMessage()];
        }
    }

    public function user_exists(string $usuario, ?string $email = null): bool
    {
        try {
            $conn = $this->getConnection();

            if ($email !== null) {
                $stmt = $conn->prepare("
                    SELECT id FROM usuario
                    WHERE usuario = :usuario OR email = :email
                    LIMIT 1
                ");
                $stmt->execute([
                    ':usuario' => $usuario,
                    ':email'   => $email,
                ]);
            } else {
                $stmt = $conn->prepare("
                    SELECT id FROM usuario
                    WHERE usuario = :usuario
                    LIMIT 1
                ");
                $stmt->execute([':usuario' => $usuario]);
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false;
        } catch (Throwable $e) {
            error_log('Error al verificar usuario: ' . $e->getMessage());
            return false;
        }
    }
}
