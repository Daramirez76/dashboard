
    // Funcionalidad del menú desplegable
    document.getElementById('menu-residente')?.addEventListener('click', function() {
      showSection('section-residente');
    });

    document.getElementById('menu-medicamento')?.addEventListener('click', function() {
      showSection('section-medicamento');
    });

    function showSection(sectionId) {
      // Ocultar todas las secciones
      document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
      });

      // Mostrar la sección seleccionada
      const targetSection = document.getElementById(sectionId);
      if (targetSection) {
        targetSection.classList.add('active');
      }
    }

    // Opcional: mostrar por defecto la sección de residentes al cargar
    showSection('section-residente');
