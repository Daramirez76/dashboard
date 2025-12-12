<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Resident.php';

class ResidentController
{
    /** @var Resident */
    private $residentModel;

    public function __construct()
    {
        $this->residentModel = new Resident();
    }

    /**
     * Lista todos los residentes activos.
     *
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function listResidents(): array
    {
        try {
            $residents = $this->residentModel->getAllResidents();
            return [
                'success' => true,
                'data'    => $residents,
                'message' => 'Residentes obtenidos exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error al listar residentes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene un residente por ID.
     *
     * @param int $id
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function getResident(int $id): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => 'ID de residente requerido'
                ];
            }

            $resident = $this->residentModel->getResidentById($id);

            if ($resident) {
                return [
                    'success' => true,
                    'data'    => $resident,
                    'message' => 'Residente obtenido exitosamente'
                ];
            }

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Residente no encontrado'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Error al obtener residente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Añade un nuevo residente.
     *
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function addResident(array $data): array
    {
        try {
            [$success, $message] = $this->residentModel->createResident($data);
            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al añadir residente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un residente.
     *
     * @param int $id
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateResident(int $id, array $data): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'message' => 'ID de residente requerido'
                ];
            }

            [$success, $message] = $this->residentModel->updateResident($id, $data);
            return [
                'success' => $success,
                'message' => $message
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar residente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina (desactiva) un residente.
     *
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function removeResident(int $id): array
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'message' => 'ID de residente requerido'
                ];
            }

            $success = $this->residentModel->deleteResident($id);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Residente eliminado exitosamente'
                ];
            }

            return [
                'success' => false,
                'message' => 'No se pudo eliminar el residente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar residente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Busca residentes.
     *
     * @param string $search
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function searchResident(string $search): array
    {
        try {
            if (empty($search)) {
                return [
                    'success' => false,
                    'data'    => [],
                    'message' => 'Término de búsqueda requerido'
                ];
            }

            $residents = $this->residentModel->searchResidents($search);
            return [
                'success' => true,
                'data'    => $residents,
                'message' => 'Búsqueda realizada exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error al buscar residentes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene residentes por estado de salud.
     *
     * @param string $status
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function getResidentsByStatus(string $status): array
    {
        try {
            if (empty($status)) {
                return [
                    'success' => false,
                    'data'    => [],
                    'message' => 'Estado de salud requerido'
                ];
            }

            $residents = $this->residentModel->getResidentsByStatus($status);
            return [
                'success' => true,
                'data'    => $residents,
                'message' => 'Residentes por estado obtenidos exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error al obtener residentes por estado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cuenta de residentes activos.
     *
     * @return array ['success' => bool, 'data' => int, 'message' => string]
     */
    public function countResidents(): array
    {
        try {
            $count = $this->residentModel->countActiveResidents();
            return [
                'success' => true,
                'data'    => $count,
                'message' => 'Conteo realizado exitosamente'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'data'    => 0,
                'message' => 'Error al contar residentes: ' . $e->getMessage()
            ];
        }
    }
}
