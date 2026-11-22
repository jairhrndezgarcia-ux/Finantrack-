// Esperar a que todo el DOM esté completamente cargado
// Este archivo se carga en todas las páginas, por eso cada bloque
// revisa primero que sus elementos existan en la página actual.
document.addEventListener('DOMContentLoaded', () => {

  /* ===================================================
     1. CALCULADORA INTERACTIVA DE AHORRO (index.php)
     =================================================== */
  const btnCalcular = document.getElementById('btn-calcular');

  if (btnCalcular) {
    const inputIngreso = document.getElementById('calc-ingreso');
    const inputGasto = document.getElementById('calc-gasto');
    const cajaResultado = document.getElementById('resultado-calculadora');
    const montoResultado = document.getElementById('monto-resultado');
    const mensajeResultado = document.getElementById('mensaje-resultado');

    btnCalcular.addEventListener('click', () => {
      const ingreso = parseFloat(inputIngreso.value) || 0;
      const gasto = parseFloat(inputGasto.value) || 0;

      if (ingreso <= 0) {
        alert('Por favor, ingresa un valor de ingreso mensual válido mayor a 0.');
        return;
      }
      if (gasto < 0) {
        alert('Los gastos no pueden ser negativos.');
        return;
      }

      const ahorroCalculado = ingreso - gasto;

      // Formatear a moneda ($)
      montoResultado.textContent = `$${ahorroCalculado.toFixed(2)}`;

      if (ahorroCalculado > 0) {
        montoResultado.style.color = '#16a34a'; // Verde
        mensajeResultado.textContent = '¡Excelente! Tienes capacidad para destinar este saldo a tus metas de ahorro.';
      } else if (ahorroCalculado === 0) {
        montoResultado.style.color = '#d97706'; // Naranja
        mensajeResultado.textContent = 'Tus ingresos y gastos están en equilibrio exacto. Intenta reducir gastos fijos.';
      } else {
        montoResultado.style.color = '#dc2626'; // Rojo
        mensajeResultado.textContent = 'Atención: Tus gastos superan tus ingresos. Revisa tus categorías presupuestadas.';
      }

      // Mostrar el contenedor de resultado quitando la clase 'oculto'
      cajaResultado.classList.remove('oculto');
    });
  }


  /* ===================================================
     2. VALIDACIÓN DE CONTRASEÑAS EN TIEMPO REAL (Registro)
     =================================================== */
  const formRegistro = document.getElementById('form-registro');

  if (formRegistro) {
    const passInput = document.getElementById('reg-password');
    const confirmInput = document.getElementById('reg-confirmar');
    const errorPasswordSpan = document.getElementById('error-password');

    function validarPasswords() {
      const pass = passInput.value;
      const confirm = confirmInput.value;

      if (pass.length > 0 && pass.length < 6) {
        errorPasswordSpan.textContent = 'La contraseña debe tener al menos 6 caracteres.';
        return false;
      }

      if (confirm.length > 0 && pass !== confirm) {
        errorPasswordSpan.textContent = 'Las contraseñas no coinciden.';
        return false;
      }

      errorPasswordSpan.textContent = ''; // Limpiar mensaje si es correcto
      return true;
    }

    // Eventos 'input' para validar mientras el usuario escribe
    passInput.addEventListener('input', validarPasswords);
    confirmInput.addEventListener('input', validarPasswords);

    // Validación al enviar el formulario (submit)
    formRegistro.addEventListener('submit', (event) => {
      if (!validarPasswords() || passInput.value !== confirmInput.value) {
        event.preventDefault(); // Detener envío si hay errores
        alert('Por favor, corrige los errores en el formulario antes de registrarte.');
      }
    });
  }


  /* ===================================================
     3. VALIDACIÓN DEL FORMULARIO DE LOGIN
     =================================================== */
  const formLogin = document.getElementById('form-login');

  if (formLogin) {
    const correoInput = document.getElementById('login-correo');
    const passLoginInput = document.getElementById('login-password');
    const errorLogin = document.getElementById('error-login');
    const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    formLogin.addEventListener('submit', (event) => {
      let mensaje = '';

      if (!patronCorreo.test(correoInput.value.trim())) {
        mensaje = 'Escribe un correo electrónico válido.';
      } else if (passLoginInput.value.length === 0) {
        mensaje = 'Escribe tu contraseña.';
      }

      errorLogin.textContent = mensaje;
      if (mensaje) {
        event.preventDefault(); // No enviar al servidor si falta algo
      }
    });
  }


  /* ===================================================
     4. MÓDULO DE CATEGORÍAS (categorias.php y admin/categorias.php)
     =================================================== */
  const modalCategoria = document.getElementById('modal-categoria');

  if (modalCategoria) {
    const formCategoria = document.getElementById('form-categoria');
    const campoAccion = document.getElementById('cat-accion');
    const campoId = document.getElementById('cat-id');
    const campoNombre = document.getElementById('cat-nombre');
    const campoTipo = document.getElementById('cat-tipo');
    const campoColor = document.getElementById('cat-color');
    const notaTipo = document.getElementById('nota-tipo');
    const errorCategoria = document.getElementById('error-categoria');
    const tituloModal = document.getElementById('modal-titulo');

    // --- Vista previa en vivo ---
    const vistaPrevia = document.getElementById('vista-previa');
    const previaIcono = document.getElementById('previa-icono');
    const previaNombre = document.getElementById('previa-nombre');
    const previaTipo = document.getElementById('previa-tipo');

    function iconoSeleccionado() {
      const marcado = formCategoria.querySelector('input[name="icono"]:checked');
      return marcado ? marcado.value : '💰';
    }

    function actualizarVistaPrevia() {
      vistaPrevia.style.setProperty('--color-cat', campoColor.value);
      previaIcono.textContent = iconoSeleccionado();
      previaNombre.textContent = campoNombre.value.trim() || 'Nombre de la categoría';
      const esIngreso = campoTipo.value === 'ingreso';
      previaTipo.textContent = esIngreso ? 'Ingreso' : 'Gasto';
      previaTipo.className = `badge badge-${campoTipo.value}`;
    }

    formCategoria.addEventListener('input', actualizarVistaPrevia);
    formCategoria.addEventListener('change', actualizarVistaPrevia);

    // --- Abrir el modal (alta o modificación) ---
    function abrirModal(datos) {
      const editando = Boolean(datos);
      formCategoria.reset();
      errorCategoria.textContent = '';

      campoAccion.value = editando ? 'editar' : 'crear';
      campoId.value = editando ? datos.id : '0';
      tituloModal.textContent = editando ? 'Editar categoría' : 'Nueva categoría';

      if (editando) {
        // Precarga de datos de la categoría seleccionada
        campoNombre.value = datos.nombre;
        campoTipo.value = datos.tipo;
        campoColor.value = datos.color;
        const radioIcono = [...formCategoria.querySelectorAll('input[name="icono"]')]
          .find((radio) => radio.value === datos.icono);
        if (radioIcono) radioIcono.checked = true;
      }

      // Si ya tiene movimientos, no se permite cambiar el tipo
      const bloquearTipo = editando && Number(datos.usos) > 0;
      campoTipo.disabled = bloquearTipo;
      notaTipo.classList.toggle('oculto', !bloquearTipo);

      actualizarVistaPrevia();
      modalCategoria.showModal();
      campoNombre.focus();
    }

    document.getElementById('btn-nueva-categoria')
      .addEventListener('click', () => abrirModal(null));

    document.querySelectorAll('.btn-editar').forEach((boton) => {
      boton.addEventListener('click', () => abrirModal(boton.dataset));
    });

    // --- Cerrar el modal ---
    document.getElementById('btn-cancelar-modal')
      .addEventListener('click', () => modalCategoria.close());

    modalCategoria.addEventListener('click', (event) => {
      if (event.target === modalCategoria) modalCategoria.close(); // clic fuera del formulario
    });

    // --- Validación antes de guardar ---
    formCategoria.addEventListener('submit', (event) => {
      const nombre = campoNombre.value.trim();
      if (nombre.length === 0 || nombre.length > 50) {
        event.preventDefault();
        errorCategoria.textContent = 'Escribe un nombre de máximo 50 caracteres.';
        campoNombre.focus();
        return;
      }
      // Un <select> deshabilitado no se envía: se habilita justo antes de enviar
      campoTipo.disabled = false;
    });

    // --- Baja con validación y confirmación ---
    document.querySelectorAll('.form-eliminar').forEach((form) => {
      form.addEventListener('submit', (event) => {
        const nombre = form.dataset.nombre;
        const usos = Number(form.dataset.usos);

        if (usos > 0) {
          event.preventDefault();
          alert(`No puedes eliminar "${nombre}" porque tiene ${usos} movimiento(s) ligados.`);
          return;
        }
        if (!confirm(`¿Seguro que quieres eliminar la categoría "${nombre}"?`)) {
          event.preventDefault();
        }
      });
    });

    // --- Filtro por tipo y búsqueda por nombre ---
    const botonesFiltro = document.querySelectorAll('.filtro');
    const campoBuscar = document.getElementById('buscar-categoria');
    const tarjetas = document.querySelectorAll('#grid-categorias .tarjeta-categoria');
    const sinResultados = document.getElementById('sin-resultados');
    let filtroActual = 'todas';

    function aplicarFiltros() {
      const texto = campoBuscar.value.trim().toLowerCase();
      let visibles = 0;

      tarjetas.forEach((tarjeta) => {
        const coincideTipo = filtroActual === 'todas' || tarjeta.dataset.tipo === filtroActual;
        const coincideTexto = tarjeta.dataset.nombre.includes(texto);
        const mostrar = coincideTipo && coincideTexto;
        tarjeta.classList.toggle('oculto', !mostrar);
        if (mostrar) visibles++;
      });

      sinResultados.classList.toggle('oculto', visibles > 0);
    }

    botonesFiltro.forEach((boton) => {
      boton.addEventListener('click', () => {
        botonesFiltro.forEach((b) => b.classList.remove('activo'));
        boton.classList.add('activo');
        filtroActual = boton.dataset.filtro;
        aplicarFiltros();
      });
    });

    campoBuscar.addEventListener('input', aplicarFiltros);
  }


  /* ===================================================
     5. MENSAJES EMERGENTES (toast) - se ocultan solos
     =================================================== */
  const toast = document.querySelector('.toast');

  if (toast) {
    // Clic para cerrarlo antes de tiempo
    toast.addEventListener('click', () => toast.remove());

    setTimeout(() => {
      toast.classList.add('toast-saliendo');
      setTimeout(() => toast.remove(), 400); // esperar la animación de salida
    }, 5000);
  }

});
