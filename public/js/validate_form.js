document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formRegister");
  
    if (!form) {
        console.error("No se encontró el formulario #formRegister");
        return; 
    }
  
    form.addEventListener("submit", function (e) {
      // Evitamos que el formulario se envíe o recargue la página automáticamente
      e.preventDefault();
  
      // Limpiamos estados anteriores
      limpiarErrores();
  
      const errores = [];
  
      // Obtenemos los elementos por el ID exacto que tienes en el HTML
      const nombre       = document.getElementById("nombre");
      const apellido     = document.getElementById("apellido");
      const tipoDoc      = document.getElementById("tipoDoc");   
      const nroDoc       = document.getElementById("numDoc");    
      const direccion    = document.getElementById("direccion");
      const telefono     = document.getElementById("telefono");
      const correo       = document.getElementById("correo");
      const cargo        = document.getElementById("cargo");     
      const usuario      = document.getElementById("usuario");
      const contrasena   = document.getElementById("contrasena"); // (sin ñ)
  
      // --------- Validaciones ----------
      
      if (!valor(nombre)) {
        errores.push("El nombre es obligatorio.");
        marcarError(nombre);
      }
  
      if (!valor(apellido)) {
        errores.push("El apellido es obligatorio.");
        marcarError(apellido);
      }
  
      if (!valor(tipoDoc)) {
        errores.push("Debe indicar el tipo de documento.");
        marcarError(tipoDoc);
      }
  
      if (!valor(nroDoc)) {
        errores.push("El número de documento es obligatorio.");
        marcarError(nroDoc);
      } else if (!/^[0-9]{6,15}$/.test(nroDoc.value.trim())) {
        errores.push("El número de documento debe contener solo números (6 a 15 dígitos).");
        marcarError(nroDoc);
      }
  
      if (!valor(direccion)) {
        errores.push("La dirección es obligatoria.");
        marcarError(direccion);
      }
  
      if (!valor(telefono)) {
        errores.push("El teléfono es obligatorio.");
        marcarError(telefono);
      } else if (!/^[0-9]{7,15}$/.test(telefono.value.trim())) {
        errores.push("El teléfono debe contener solo números válidos.");
        marcarError(telefono);
      }
  
      if (!valor(correo)) {
        errores.push("El correo electrónico es obligatorio.");
        marcarError(correo);
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value.trim())) {
        errores.push("El correo electrónico no tiene un formato válido.");
        marcarError(correo);
      }
  
      if (!valor(cargo)) {
        errores.push("El cargo es obligatorio.");
        marcarError(cargo);
      }
  
      if (!valor(usuario)) {
        errores.push("El nombre de usuario es obligatorio.");
        marcarError(usuario);
      } else if (usuario.value.trim().length < 4) {
        errores.push("El nombre de usuario debe tener al menos 4 caracteres.");
        marcarError(usuario);
      }
  
      if (!valor(contrasena)) {
        errores.push("La contraseña es obligatoria.");
        marcarError(contrasena);
      } else if (contrasena.value.length < 8) {
        errores.push("La contraseña debe tener mínimo 8 caracteres.");
        marcarError(contrasena);
      }
  
      // --------- Resultado ---------
      
      if (errores.length > 0) {
        // Mostramos alerta con los errores
        alert("Por favor corrige lo siguiente:\n\n- " + errores.join("\n- "));
      } else {
        alert("¡Registro exitoso!");
        window.location.href = './login_employees.html';
      }
    });
  
    // --------- Helpers ---------
    
    function valor(input) {
      return input && input.value.trim() !== "";
    }
  
    function marcarError(input) {
      if (!input) return;
      // Usamos 'is-invalid' que es la clase nativa de Bootstrap para bordes rojos
      input.classList.add("is-invalid");
      
      // Si quieres mantener tu estilo CSS personalizado 'campo-error', descomenta la siguiente linea:
      input.classList.add("campo-error");
    }
  
    function limpiarErrores() {
      // Quitamos la clase de error de todos los inputs
      document.querySelectorAll(".form-control").forEach(el => {
        el.classList.remove("is-invalid");
        el.classList.remove("campo-error");
      });
    }
  });