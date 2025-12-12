<?php
declare(strict_types=1);

class Resident
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
     * Crea la tabla de residentes si no existe.
     */
    private function initDb(): void
    {
        try {
            $conn = $this->getConnection();

            $conn->exec("
                CREATE TABLE IF NOT EXISTS residents (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    nombre VARCHAR(80) NOT NULL,
                    apellido VARCHAR(80) NOT NULL,
                    fecha_nacimiento DATE NOT NULL,
                    tipo_doc VARCHAR(30) NOT NULL,
                    num_doc VARCHAR(30) NOT NULL UNIQUE,
                    direccion VARCHAR(150) NOT NULL,
                    telefono VARCHAR(30),
                    email VARCHAR(120),
                    estado_salud VARCHAR(50),
                    alergias TEXT,
                    medicamentos_actuales TEXT,
                    fecha_ingreso DATE NOT NULL,
                    fecha_egreso DATE,
                    activo BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uk_residents_num_doc (num_doc),
                    KEY idx_residents_activo (activo),
                    KEY idx_residents_fecha_ingreso (fecha_ingreso)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            error_log('Error al inicializar tabla residents: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los residentes activos.
     *
     * @return array
     */
    public function getAllResidents(): array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT id, nombre, apellido, fecha_nacimiento, tipo_doc, num_doc,
                       direccion, telefono, email, estado_salud, alergias,
                       fecha_ingreso, activo, created_at
                FROM residents
                WHERE activo = TRUE
                ORDER BY fecha_ingreso DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al obtener residentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un residente por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getResidentById(int $id): ?array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT id, nombre, apellido, fecha_nacimiento, tipo_doc, num_doc,
                       direccion, telefono, email, estado_salud, alergias,
                       medicamentos_actuales, fecha_ingreso, fecha_egreso, activo, created_at
                FROM residents
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener residente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo residente.
     *
     * @param array $data
     * @return array [success, message]
     */
    public function createResident(array $data): array
    {
        try {
            $required = ['nombre', 'apellido', 'fecha_nacimiento', 'tipo_doc', 'num_doc', 'direccion', 'fecha_ingreso'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                    return [false, "Campo requerido: {$field}"];
                }
            }

            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO residents
                    (nombre, apellido, fecha_nacimiento, tipo_doc, num_doc, direccion, 
                     telefono, email, estado_salud, alergias, medicamentos_actuales, fecha_ingreso)
                VALUES
                    (:nombre, :apellido, :fecha_nacimiento, :tipo_doc, :num_doc, :direccion,
                     :telefono, :email, :estado_salud, :alergias, :medicamentos_actuales, :fecha_ingreso)
            ");

            $stmt->execute([
                ':nombre'                 => trim((string)$data['nombre']),
                ':apellido'               => trim((string)$data['apellido']),
                ':fecha_nacimiento'       => $data['fecha_nacimiento'],
                ':tipo_doc'               => trim((string)$data['tipo_doc']),
                ':num_doc'                => trim((string)$data['num_doc']),
                ':direccion'              => trim((string)$data['direccion']),
                ':telefono'               => $data['telefono'] ?? null,
                ':email'                  => $data['email'] ?? null,
                ':estado_salud'           => $data['estado_salud'] ?? null,
                ':alergias'               => $data['alergias'] ?? null,
                ':medicamentos_actuales'  => $data['medicamentos_actuales'] ?? null,
                ':fecha_ingreso'          => $data['fecha_ingreso'],
            ]);

            return [true, 'Residente creado exitosamente'];
        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                return [false, 'El documento del residente ya existe'];
            }
            return [false, 'Error al crear residente: ' . $e->getMessage()];
        } catch (Throwable $e) {
            return [false, 'Error al crear residente: ' . $e->getMessage()];
        }
    }

    /**
     * Actualiza un residente.
     *
     * @param int $id
     * @param array $data
     * @return array [success, message]
     */
    public function updateResident(int $id, array $data): array
    {
        try {
            if (empty($id)) {
                return [false, 'ID de residente requerido'];
            }

            $conn = $this->getConnection();

            // Verificar que el residente existe
            $stmt = $conn->prepare("SELECT id FROM residents WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return [false, 'Residente no encontrado'];
            }

            // Construir consulta UPDATE dinámica
            $updateFields = [];
            $params = [':id' => $id];

            $allowedFields = ['nombre', 'apellido', 'estado_salud', 'alergias', 'medicamentos_actuales', 
                            'email', 'telefono', 'direccion', 'fecha_egreso', 'activo'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                return [false, 'No hay campos para actualizar'];
            }

            $sql = "UPDATE residents SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            return [true, 'Residente actualizado exitosamente'];
        } catch (Throwable $e) {
            return [false, 'Error al actualizar residente: ' . $e->getMessage()];
        }
    }

    /**
     * Desactiva (elimina lógicamente) un residente.
     *
     * @param int $id
     * @return bool
     */
    public function deleteResident(int $id): bool
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("UPDATE residents SET activo = FALSE WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('Error al eliminar residente: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene residentes por estado de salud.
     *
     * @param string $status
     * @return array
     */
    public function getResidentsByStatus(string $status): array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT id, nombre, apellido, fecha_nacimiento, tipo_doc, num_doc,
                       estado_salud, alergias, fecha_ingreso, created_at
                FROM residents
                WHERE estado_salud = :status AND activo = TRUE
                ORDER BY fecha_ingreso DESC
            ");
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al obtener residentes por estado: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca residentes por nombre, apellido o documento.
     *
     * @param string $search
     * @return array
     */
    public function searchResidents(string $search): array
    {
        try {
            $conn = $this->getConnection();
            $searchTerm = '%' . $search . '%';
            
            $stmt = $conn->prepare("
                SELECT id, nombre, apellido, fecha_nacimiento, tipo_doc, num_doc,
                       estado_salud, alergias, fecha_ingreso, created_at
                FROM residents
                WHERE (nombre LIKE :search OR apellido LIKE :search OR num_doc LIKE :search)
                  AND activo = TRUE
                ORDER BY nombre, apellido
            ");
            $stmt->execute([':search' => $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al buscar residentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta residentes activos.
     *
     * @return int
     */
    public function countActiveResidents(): int
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM residents WHERE activo = TRUE");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Throwable $e) {
            error_log('Error al contar residentes: ' . $e->getMessage());
            return 0;
        }
    }
}
