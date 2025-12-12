/**
 * Script de validación y gestión de residentes
 * Conecta con la API: /public/api/resident.php
 */

document.addEventListener('DOMContentLoaded', function() {
    const formResidentes = document.getElementById('formResidentes');
    const tableResidentes = document.getElementById('tableResidentes');
    
    if (formResidentes) {
        formResidentes.addEventListener('submit', handleAddResident);
    }

    // Cargar residentes al abrir la página
    loadResidents();
});

/**
 * Maneja el envío del formulario para agregar un residente
 * @param {Event} event
 */
async function handleAddResident(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Validaciones básicas
    if (!data.nombre || !data.apellido || !data.fecha_nacimiento || !data.tipo_doc || !data.num_doc || !data.direccion || !data.fecha_ingreso) {
        showAlert('Por favor completa todos los campos requeridos', 'danger');
        return;
    }

    const submitButton = form.querySelector('[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Guardando...';

    try {
        const response = await fetch('/public/api/resident.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Residente agregado exitosamente', 'success');
            form.reset();
            loadResidents();
        } else {
            showAlert(result.message || 'Error al agregar residente', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión con el servidor', 'danger');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

/**
 * Carga y muestra todos los residentes en la tabla
 */
async function loadResidents() {
    try {
        const response = await fetch('/public/api/resident.php?');
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            displayResidents(result.data);
        } else {
            const tableBody = document.querySelector('tbody');
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="9" class="text-center">No hay residentes registrados</td></tr>';
            }
        }
    } catch (error) {
        console.error('Error al cargar residentes:', error);
        showAlert('Error al cargar residentes', 'danger');
    }
}

/**
 * Muestra los residentes en la tabla
 * @param {Array} residents
 */
function displayResidents(residents) {
    const tableBody = document.querySelector('tbody');
    if (!tableBody) return;

    tableBody.innerHTML = residents.map(resident => `
        <tr>
            <td>${resident.id}</td>
            <td>${resident.nombre}</td>
            <td>${resident.apellido}</td>
            <td>${resident.fecha_nacimiento}</td>
            <td>${resident.tipo_doc}</td>
            <td>${resident.num_doc}</td>
            <td>${resident.estado_salud || 'No especificado'}</td>
            <td>${resident.fecha_ingreso}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editResident(${resident.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="deleteResident(${resident.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

/**
 * Edita un residente
 * @param {number} residentId
 */
async function editResident(residentId) {
    try {
        const response = await fetch(`/public/api/resident.php?id=${residentId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const resident = result.data;
            console.log('Editar residente:', resident);
            showAlert('Función de edición en desarrollo', 'info');
        } else {
            showAlert('No se pudo cargar el residente', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el residente', 'danger');
    }
}

/**
 * Elimina (desactiva) un residente
 * @param {number} residentId
 */
async function deleteResident(residentId) {
    if (!confirm('¿Está seguro de que desea eliminar este residente?')) {
        return;
    }

    try {
        const response = await fetch('/public/api/resident.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: residentId })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Residente eliminado exitosamente', 'success');
            loadResidents();
        } else {
            showAlert(result.message || 'Error al eliminar residente', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión con el servidor', 'danger');
    }
}

/**
 * Busca residentes por nombre, apellido o documento
 * @param {string} searchTerm
 */
async function searchResidents(searchTerm) {
    if (!searchTerm.trim()) {
        loadResidents();
        return;
    }

    try {
        const response = await fetch(`/public/api/resident.php?search=${encodeURIComponent(searchTerm)}`);
        const result = await response.json();

        if (result.success && result.data) {
            displayResidents(result.data);
        } else {
            showAlert('No se encontraron resultados', 'info');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al buscar residentes', 'danger');
    }
}

/**
 * Obtiene residentes por estado de salud
 * @param {string} status
 */
async function getResidentsByStatus(status) {
    if (!status) {
        loadResidents();
        return;
    }

    try {
        const response = await fetch(`/public/api/resident.php?status=${encodeURIComponent(status)}`);
        const result = await response.json();

        if (result.success && result.data) {
            displayResidents(result.data);
        } else {
            showAlert('No se encontraron residentes con ese estado', 'info');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al filtrar residentes', 'danger');
    }
}

/**
 * Muestra una alerta visual
 * @param {string} message
 * @param {string} type
 */
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.setAttribute('role', 'alert');
    alert.textContent = message;
    
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        border-radius: 12px;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;

    const colors = {
        'success': { bg: '#d4edda', text: '#155724', border: '#c3e6cb' },
        'danger': { bg: '#f8d7da', text: '#721c24', border: '#f5c6cb' },
        'warning': { bg: '#fff3cd', text: '#856404', border: '#ffeeba' },
        'info': { bg: '#d1ecf1', text: '#0c5460', border: '#bee5eb' }
    };

    const color = colors[type] || colors['info'];
    alert.style.backgroundColor = color.bg;
    alert.style.color = color.text;
    alert.style.border = `1px solid ${color.border}`;

    document.body.appendChild(alert);

    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => alert.remove(), 300);
    }, 4000);
}

