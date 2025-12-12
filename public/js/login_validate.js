document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});

/**
 * Maneja el envío del formulario de login
 * @param {Event} event - Evento del formulario
 */
async function handleLogin(event) {
    event.preventDefault();

    const usuario = document.getElementById('usuario').value.trim();
    const password = document.getElementById('contrasena').value.trim();

    // Validar campos
    if (!usuario || !password) {
        showAlert('Usuario y contraseña son requeridos', 'danger');
        return;
    }

    // Desabilitar botón mientras se procesa
    const submitButton = event.target.querySelector('[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Procesando...';

    try {
        const response = await fetch('/public/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                usuario: usuario,
                password: password
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('¡Bienvenido! Redirigiendo...', 'success');
            // Guardar datos en sessionStorage
            sessionStorage.setItem('usuarioId', data.data.usuario_id);
            sessionStorage.setItem('usuario', data.data.usuario);
            sessionStorage.setItem('email', data.data.email);
            
            // Redirigir después de 1.5 segundos
            setTimeout(() => {
                window.location.href = '/app/views/dashboard/dashboard.html';
            }, 1500);
        } else {
            showAlert(data.message || 'Error en la autenticación', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión con el servidor', 'danger');
    } finally {
        // Restaurar botón
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

/**
 * Muestra una alerta visual
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de alerta: 'success', 'danger', 'warning', 'info'
 */
function showAlert(message, type = 'info') {
    // Crear elemento de alerta
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.setAttribute('role', 'alert');
    alert.textContent = message;
    
    // Estilos personalizados
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

    // Determinar colores según tipo
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

    // Remover alerta después de 4 segundos
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => alert.remove(), 300);
    }, 4000);
}

// Agregar animaciones CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
