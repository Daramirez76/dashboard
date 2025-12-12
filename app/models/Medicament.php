<?php
declare(strict_types=1);

class Medicament
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
     * Crea la tabla de medicamentos si no existe.
     */
    private function initDb(): void
    {
        try {
            $conn = $this->getConnection();

            $conn->exec("
                CREATE TABLE IF NOT EXISTS medicaments (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    residente_id INT UNSIGNED NOT NULL,
                    nombre VARCHAR(120) NOT NULL,
                    dosis VARCHAR(50) NOT NULL,
                    frecuencia VARCHAR(100) NOT NULL,
                    indicaciones TEXT,
                    fecha_inicio DATE NOT NULL,
                    fecha_fin DATE,
                    stock INT UNSIGNED DEFAULT 0,
                    laboratorio VARCHAR(100),
                    principio_activo VARCHAR(120),
                    activo BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_medicaments_residente_id (residente_id),
                    KEY idx_medicaments_activo (activo),
                    KEY idx_medicaments_fecha_inicio (fecha_inicio),
                    CONSTRAINT fk_medicaments_residents
                        FOREIGN KEY (residente_id)
                        REFERENCES residents(id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            error_log('Error al inicializar tabla medicaments: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los medicamentos activos.
     *
     * @return array
     */
    public function getAllMedicaments(): array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT m.id, m.residente_id, m.nombre, m.dosis, m.frecuencia,
                       m.indicaciones, m.fecha_inicio, m.fecha_fin, m.stock,
                       m.laboratorio, m.principio_activo, m.created_at,
                       CONCAT(r.nombre, ' ', r.apellido) as residente_nombre
                FROM medicaments m
                JOIN residents r ON m.residente_id = r.id
                WHERE m.activo = TRUE
                ORDER BY m.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al obtener medicamentos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un medicamento por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getMedicamentById(int $id): ?array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT m.id, m.residente_id, m.nombre, m.dosis, m.frecuencia,
                       m.indicaciones, m.fecha_inicio, m.fecha_fin, m.stock,
                       m.laboratorio, m.principio_activo, m.activo, m.created_at,
                       CONCAT(r.nombre, ' ', r.apellido) as residente_nombre
                FROM medicaments m
                JOIN residents r ON m.residente_id = r.id
                WHERE m.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener medicamento: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo medicamento.
     *
     * @param array $data
     * @return array [success, message]
     */
    public function createMedicament(array $data): array
    {
        try {
            $required = ['residente_id', 'nombre', 'dosis', 'frecuencia', 'fecha_inicio'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                    return [false, "Campo requerido: {$field}"];
                }
            }

            $conn = $this->getConnection();

            // Verificar que el residente existe
            $stmt = $conn->prepare("SELECT id FROM residents WHERE id = :residente_id LIMIT 1");
            $stmt->execute([':residente_id' => $data['residente_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return [false, 'Residente no encontrado'];
            }

            $stmt = $conn->prepare("
                INSERT INTO medicaments
                    (residente_id, nombre, dosis, frecuencia, indicaciones, 
                     fecha_inicio, fecha_fin, stock, laboratorio, principio_activo)
                VALUES
                    (:residente_id, :nombre, :dosis, :frecuencia, :indicaciones,
                     :fecha_inicio, :fecha_fin, :stock, :laboratorio, :principio_activo)
            ");

            $stmt->execute([
                ':residente_id'      => (int)$data['residente_id'],
                ':nombre'            => trim((string)$data['nombre']),
                ':dosis'             => trim((string)$data['dosis']),
                ':frecuencia'        => trim((string)$data['frecuencia']),
                ':indicaciones'      => $data['indicaciones'] ?? null,
                ':fecha_inicio'      => $data['fecha_inicio'],
                ':fecha_fin'         => $data['fecha_fin'] ?? null,
                ':stock'             => (int)($data['stock'] ?? 0),
                ':laboratorio'       => $data['laboratorio'] ?? null,
                ':principio_activo'  => $data['principio_activo'] ?? null,
            ]);

            return [true, 'Medicamento creado exitosamente'];
        } catch (Throwable $e) {
            return [false, 'Error al crear medicamento: ' . $e->getMessage()];
        }
    }

    /**
     * Actualiza un medicamento.
     *
     * @param int $id
     * @param array $data
     * @return array [success, message]
     */
    public function updateMedicament(int $id, array $data): array
    {
        try {
            if (empty($id)) {
                return [false, 'ID de medicamento requerido'];
            }

            $conn = $this->getConnection();

            // Verificar que el medicamento existe
            $stmt = $conn->prepare("SELECT id FROM medicaments WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return [false, 'Medicamento no encontrado'];
            }

            // Construir consulta UPDATE dinámica
            $updateFields = [];
            $params = [':id' => $id];

            $allowedFields = ['nombre', 'dosis', 'frecuencia', 'indicaciones', 'fecha_inicio', 
                            'fecha_fin', 'stock', 'laboratorio', 'principio_activo', 'activo'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                return [false, 'No hay campos para actualizar'];
            }

            $sql = "UPDATE medicaments SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            return [true, 'Medicamento actualizado exitosamente'];
        } catch (Throwable $e) {
            return [false, 'Error al actualizar medicamento: ' . $e->getMessage()];
        }
    }

    /**
     * Desactiva (elimina lógicamente) un medicamento.
     *
     * @param int $id
     * @return bool
     */
    public function deleteMedicament(int $id): bool
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("UPDATE medicaments SET activo = FALSE WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('Error al eliminar medicamento: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene medicamentos de un residente.
     *
     * @param int $residentId
     * @return array
     */
    public function getMedicamentsByResident(int $residentId): array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT id, residente_id, nombre, dosis, frecuencia,
                       indicaciones, fecha_inicio, fecha_fin, stock,
                       laboratorio, principio_activo, created_at
                FROM medicaments
                WHERE residente_id = :residente_id AND activo = TRUE
                ORDER BY fecha_inicio DESC
            ");
            $stmt->execute([':residente_id' => $residentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al obtener medicamentos del residente: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene información de stock de un medicamento.
     *
     * @param int $id
     * @return array [success, data]
     */
    public function checkStock(int $id): array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT id, nombre, stock, laboratorio, fecha_inicio
                FROM medicaments
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $medicament = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($medicament) {
                $status = 'OK';
                if ($medicament['stock'] <= 0) {
                    $status = 'AGOTADO';
                } elseif ($medicament['stock'] <= 5) {
                    $status = 'BAJO';
                }

                return [true, array_merge($medicament, ['status' => $status])];
            }

            return [false, 'Medicamento no encontrado'];
        } catch (Throwable $e) {
            return [false, 'Error al verificar stock: ' . $e->getMessage()];
        }
    }

    /**
     * Cuenta medicamentos activos.
     *
     * @return int
     */
    public function countActiveMedicaments(): int
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM medicaments WHERE activo = TRUE");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Throwable $e) {
            error_log('Error al contar medicamentos: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene medicamentos con stock bajo o agotado.
     *
     * @return array
     */
    public function getLowStockMedicaments(): array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("
                SELECT m.id, m.nombre, m.stock, r.nombre as residente_nombre
                FROM medicaments m
                JOIN residents r ON m.residente_id = r.id
                WHERE m.stock <= 5 AND m.activo = TRUE
                ORDER BY m.stock ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al obtener medicamentos con stock bajo: ' . $e->getMessage());
            return [];
        }
    }
}
