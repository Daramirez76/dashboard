<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Medicament.php';

class MedicamentController
{
    /** @var Medicament */
    private $medicamentModel;

    public function __construct()
    {
        $this->medicamentModel = new Medicament();
    }

    /**
     * Lista todos los medicamentos activos.
     *
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function listMedicaments(): array
    {
        try {
            $medicaments = $this->medicamentModel->getAllMedicaments();
            return [
                'success' => true,
                'data'    => $medicaments,
                'message' => 'Medicamentos obtenidos exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error al listar medicamentos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene un medicamento por ID.
     *
     * @param int $id
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function getMedicament(int $id): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => 'ID de medicamento requerido'
                ];
            }

            $medicament = $this->medicamentModel->getMedicamentById($id);

            if ($medicament) {
                return [
                    'success' => true,
                    'data'    => $medicament,
                    'message' => 'Medicamento obtenido exitosamente'
                ];
            }

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Medicamento no encontrado'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Error al obtener medicamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Añade un nuevo medicamento.
     *
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function addMedicament(array $data): array
    {
        try {
            [$success, $message] = $this->medicamentModel->createMedicament($data);
            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al añadir medicamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un medicamento.
     *
     * @param int $id
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateMedicament(int $id, array $data): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'message' => 'ID de medicamento requerido'
                ];
            }

            [$success, $message] = $this->medicamentModel->updateMedicament($id, $data);
            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar medicamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina (desactiva) un medicamento.
     *
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function removeMedicament(int $id): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'message' => 'ID de medicamento requerido'
                ];
            }

            $success = $this->medicamentModel->deleteMedicament($id);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Medicamento eliminado exitosamente'
                ];
            }

            return [
                'success' => false,
                'message' => 'No se pudo eliminar el medicamento'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar medicamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene medicamentos de un residente.
     *
     * @param int $residentId
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function getMedicamentsByResident(int $residentId): array
    {
        try {
            if (empty($residentId)) {
                return [
                    'success' => false,
                    'data'    => [],
                    'message' => 'ID de residente requerido'
                ];
            }

            $medicaments = $this->medicamentModel->getMedicamentsByResident($residentId);
            return [
                'success' => true,
                'data'    => $medicaments,
                'message' => 'Medicamentos del residente obtenidos exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error al obtener medicamentos del residente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica el stock de un medicamento.
     *
     * @param int $id
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function checkStock(int $id): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => 'ID de medicamento requerido'
                ];
            }

            [$success, $data] = $this->medicamentModel->checkStock($id);
            return [
                'success' => $success,
                'data'    => $success ? $data : null,
                'message' => $success ? 'Stock verificado' : $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Error al verificar stock: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cuenta medicamentos activos.
     *
     * @return array ['success' => bool, 'data' => int, 'message' => string]
     */
    public function countMedicaments(): array
    {
        try {
            $count = $this->medicamentModel->countActiveMedicaments();
            return [
                'success' => true,
                'data'    => $count,
                'message' => 'Conteo realizado exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => 0,
                'message' => 'Error al contar medicamentos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene medicamentos con stock bajo.
     *
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function getLowStockMedicaments(): array
    {
        try {
            $medicaments = $this->medicamentModel->getLowStockMedicaments();
            return [
                'success' => true,
                'data'    => $medicaments,
                'message' => 'Medicamentos con stock bajo obtenidos exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error al obtener medicamentos con stock bajo: ' . $e->getMessage()
            ];
        }
    }
}
