/**
 * Script de validación y gestión de medicamentos
 * Conecta con la API: /public/api/medicament.php
 */

document.addEventListener('DOMContentLoaded', function() {
    const formMedicamentos = document.getElementById('formMedicamentos');
    
    if (formMedicamentos) {
        formMedicamentos.addEventListener('submit', handleAddMedicament);
    }

    // Cargar medicamentos al abrir la página
    loadMedicaments();
});

/**
 * Maneja el envío del formulario para agregar un medicamento
 * @param {Event} event
 */
async function handleAddMedicament(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Validaciones básicas
    if (!data.residente_id || !data.nombre || !data.dosis || !data.frecuencia || !data.fecha_inicio) {
        showAlert('Por favor completa todos los campos requeridos', 'danger');
        return;
    }

    const submitButton = form.querySelector('[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Guardando...';

    try {
        const response = await fetch('/public/api/medicament.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Medicamento agregado exitosamente', 'success');
            form.reset();
            loadMedicaments();
        } else {
            showAlert(result.message || 'Error al agregar medicamento', 'danger');
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
 * Carga y muestra todos los medicamentos en la tabla
 */
async function loadMedicaments() {
    try {
        const response = await fetch('/public/api/medicament.php?');
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            displayMedicaments(result.data);
        } else {
            const tableBody = document.querySelector('tbody');
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="10" class="text-center">No hay medicamentos registrados</td></tr>';
            }
        }
    } catch (error) {
        console.error('Error al cargar medicamentos:', error);
        showAlert('Error al cargar medicamentos', 'danger');
    }
}

/**
 * Muestra los medicamentos en la tabla
 * @param {Array} medicaments
 */
function displayMedicaments(medicaments) {
    const tableBody = document.querySelector('tbody');
    if (!tableBody) return;

    tableBody.innerHTML = medicaments.map(med => `
        <tr>
            <td>${med.id}</td>
            <td>${med.residente_nombre || 'No especificado'}</td>
            <td>${med.nombre}</td>
            <td>${med.dosis}</td>
            <td>${med.frecuencia}</td>
            <td>${med.laboratorio || 'No especificado'}</td>
            <td><span class="badge ${med.stock <= 5 ? 'badge-warning' : 'badge-success'}">${med.stock}</span></td>
            <td>${med.fecha_inicio}</td>
            <td>${med.fecha_fin || 'Vigente'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editMedicament(${med.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="deleteMedicament(${med.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

/**
 * Edita un medicamento
 * @param {number} medicamentId
 */
async function editMedicament(medicamentId) {
    try {
        const response = await fetch(`/public/api/medicament.php?id=${medicamentId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const medicament = result.data;
            console.log('Editar medicamento:', medicament);
            showAlert('Función de edición en desarrollo', 'info');
        } else {
            showAlert('No se pudo cargar el medicamento', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el medicamento', 'danger');
    }
}

/**
 * Elimina (desactiva) un medicamento
 * @param {number} medicamentId
 */
async function deleteMedicament(medicamentId) {
    if (!confirm('¿Está seguro de que desea eliminar este medicamento?')) {
        return;
    }

    try {
        const response = await fetch('/public/api/medicament.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: medicamentId })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Medicamento eliminado exitosamente', 'success');
            loadMedicaments();
        } else {
            showAlert(result.message || 'Error al eliminar medicamento', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión con el servidor', 'danger');
    }
}

/**
 * Obtiene medicamentos de un residente específico
 * @param {number} residentId
 */
async function getMedicamentsByResident(residentId) {
    if (!residentId) {
        loadMedicaments();
        return;
    }

    try {
        const response = await fetch(`/public/api/medicament.php?resident_id=${residentId}`);
        const result = await response.json();

        if (result.success && result.data) {
            displayMedicaments(result.data);
        } else {
            showAlert('No hay medicamentos para este residente', 'info');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al obtener medicamentos del residente', 'danger');
    }
}

/**
 * Obtiene medicamentos con stock bajo
 */
async function getLowStockMedicaments() {
    try {
        const response = await fetch('/public/api/medicament.php?low_stock=1');
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            displayMedicaments(result.data);
            showAlert(`Se encontraron ${result.data.length} medicamentos con stock bajo`, 'warning');
        } else {
            showAlert('No hay medicamentos con stock bajo', 'success');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al obtener medicamentos con stock bajo', 'danger');
    }
}

/**
 * Verifica el stock de un medicamento
 * @param {number} medicamentId
 */
async function checkMedicamentStock(medicamentId) {
    try {
        const response = await fetch(`/public/api/medicament.php?stock=${medicamentId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const med = result.data;
            let message = `${med.nombre}: Stock = ${med.stock} | Estado: ${med.status}`;
            showAlert(message, med.status === 'AGOTADO' ? 'danger' : 'info');
        } else {
            showAlert('No se pudo verificar el stock', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al verificar el stock', 'danger');
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
