// Esperar a que todo el DOM esté completamente cargado
// Este archivo se carga en todas las páginas, por eso cada bloque
// revisa primero que sus elementos existan en la página actual.
document.addEventListener('DOMContentLoaded', () => {

  /* ===================================================
     0. MENÚ HAMBURGUESA
     Con sesión abre la barra lateral como cajón; sin sesión
     despliega el menú de la barra superior.
     =================================================== */
  const btnMenu = document.getElementById('btn-abrir-menu');

  if (btnMenu) {
    const panel = document.getElementById(btnMenu.getAttribute('aria-controls'));
    const fondoMenu = document.getElementById('fondo-menu');

    function abrirMenu(abrir) {
      panel.classList.toggle('abierto', abrir);
      btnMenu.setAttribute('aria-expanded', String(abrir));
      btnMenu.setAttribute('aria-label', abrir ? 'Cerrar menú' : 'Abrir menú');
      if (fondoMenu) fondoMenu.hidden = !abrir;
      // Con el cajón abierto no se desplaza la página de fondo
      document.body.style.overflow = abrir && fondoMenu ? 'hidden' : '';
    }

    btnMenu.addEventListener('click', () => abrirMenu(!panel.classList.contains('abierto')));
    if (fondoMenu) fondoMenu.addEventListener('click', () => abrirMenu(false));

    // Cerrar con Escape o al elegir un enlace
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && panel.classList.contains('abierto')) {
        abrirMenu(false);
        btnMenu.focus();
      }
    });
    panel.querySelectorAll('a').forEach((enlace) => {
      enlace.addEventListener('click', () => abrirMenu(false));
    });

    // Si la pantalla crece y el menú ya no es cajón, se limpia el estado
    window.matchMedia('(min-width: 901px)').addEventListener('change', (evento) => {
      if (evento.matches) abrirMenu(false);
    });
  }


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
     5. MÓDULO DE TRANSACCIONES (transacciones.php)
     =================================================== */
  const modalTransaccion = document.getElementById('modal-transaccion');

  if (modalTransaccion) {
    const formTrans = document.getElementById('form-transaccion');
    const campoAccionT = document.getElementById('trans-accion');
    const campoIdT = document.getElementById('trans-id');
    const campoMonto = document.getElementById('trans-monto');
    const campoFecha = document.getElementById('trans-fecha');
    const campoCategoriaT = document.getElementById('trans-categoria');
    const campoMetodo = document.getElementById('trans-metodo');
    const campoNotas = document.getElementById('trans-notas');
    const contadorNotas = document.getElementById('contador-notas');
    const errorTrans = document.getElementById('error-transaccion');
    const tituloTrans = document.getElementById('modal-trans-titulo');
    const radiosTipo = formTrans.querySelectorAll('input[name="tipo"]');
    const hoy = campoFecha.max;

    function tipoSeleccionado() {
      return formTrans.querySelector('input[name="tipo"]:checked').value;
    }

    function marcarTipo(tipo) {
      radiosTipo.forEach((radio) => { radio.checked = radio.value === tipo; });
      filtrarCategoriasPorTipo();
    }

    // Solo se muestran las categorías del tipo elegido (ingreso o gasto)
    function filtrarCategoriasPorTipo() {
      const tipo = tipoSeleccionado();
      let primeraVisible = null;

      [...campoCategoriaT.options].forEach((opcion) => {
        const visible = opcion.dataset.tipo === tipo;
        opcion.hidden = !visible;
        opcion.disabled = !visible;
        if (visible && !primeraVisible) primeraVisible = opcion;
      });

      const actual = campoCategoriaT.selectedOptions[0];
      if ((!actual || actual.disabled) && primeraVisible) {
        primeraVisible.selected = true;
      }
      modalTransaccion.dataset.tipo = tipo; // cambia el color del modal (CSS)
    }

    radiosTipo.forEach((radio) => radio.addEventListener('change', filtrarCategoriasPorTipo));

    function actualizarContadorNotas() {
      contadorNotas.textContent = `${campoNotas.value.length} / 255`;
    }
    campoNotas.addEventListener('input', actualizarContadorNotas);

    // --- Abrir modal: nuevo (con el tipo del botón) o edición (con precarga) ---
    function abrirModalTransaccion(tipoNuevo, datos) {
      const editando = Boolean(datos);
      formTrans.reset();
      errorTrans.textContent = '';

      campoAccionT.value = editando ? 'editar' : 'crear';
      campoIdT.value = editando ? datos.id : '0';
      tituloTrans.textContent = editando ? 'Editar movimiento' : (tipoNuevo === 'ingreso' ? 'Nuevo ingreso' : 'Nuevo gasto');

      if (editando) {
        marcarTipo(datos.tipo);
        campoMonto.value = datos.monto;
        campoFecha.value = datos.fecha;
        campoCategoriaT.value = datos.categoria;
        campoMetodo.value = datos.metodo;
        campoNotas.value = datos.notas;
      } else {
        marcarTipo(tipoNuevo);
        campoFecha.value = hoy;
      }

      actualizarContadorNotas();
      modalTransaccion.showModal();
      campoMonto.focus();
    }

    document.querySelectorAll('[data-nuevo]').forEach((boton) => {
      boton.addEventListener('click', () => abrirModalTransaccion(boton.dataset.nuevo, null));
    });

    document.querySelectorAll('.btn-editar-trans').forEach((boton) => {
      boton.addEventListener('click', () => abrirModalTransaccion(null, boton.dataset));
    });

    document.getElementById('btn-cancelar-trans')
      .addEventListener('click', () => modalTransaccion.close());

    modalTransaccion.addEventListener('click', (event) => {
      if (event.target === modalTransaccion) modalTransaccion.close();
    });

    // --- Validación antes de enviar ---
    formTrans.addEventListener('submit', (event) => {
      let mensaje = '';
      const monto = Number(campoMonto.value);

      if (!campoMonto.value || monto <= 0) {
        mensaje = 'Escribe un monto mayor a 0.';
      } else if (!/^\d+(\.\d{1,2})?$/.test(campoMonto.value)) {
        mensaje = 'El monto puede tener máximo 2 decimales.';
      } else if (!campoFecha.value) {
        mensaje = 'Selecciona una fecha.';
      } else if (campoFecha.value > hoy) {
        mensaje = 'La fecha no puede ser futura.';
      } else if (!campoCategoriaT.value || campoCategoriaT.selectedOptions[0].disabled) {
        mensaje = 'Selecciona una categoría.';
      }

      if (mensaje) {
        event.preventDefault();
        errorTrans.textContent = mensaje;
      }
    });

    // --- Borrado lógico con confirmación ---
    document.querySelectorAll('.form-eliminar-trans').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (!confirm(`¿Eliminar el movimiento "${form.dataset.descripcion}"?\nEl saldo se recalculará.`)) {
          event.preventDefault();
        }
      });
    });

    // --- Rangos rápidos de fechas para los filtros ---
    const formFiltros = document.getElementById('form-filtros');
    const filtroDesde = document.getElementById('f-desde');
    const filtroHasta = document.getElementById('f-hasta');

    // Fecha local en formato AAAA-MM-DD (sin desfase por zona horaria)
    function aISO(fecha) {
      const mes = String(fecha.getMonth() + 1).padStart(2, '0');
      const dia = String(fecha.getDate()).padStart(2, '0');
      return `${fecha.getFullYear()}-${mes}-${dia}`;
    }

    document.querySelectorAll('[data-rango]').forEach((boton) => {
      boton.addEventListener('click', () => {
        const ahora = new Date();
        const anio = ahora.getFullYear();
        const mes = ahora.getMonth();
        let desde;
        let hasta;

        switch (boton.dataset.rango) {
          case 'mes':
            desde = new Date(anio, mes, 1);
            hasta = new Date(anio, mes + 1, 0); // día 0 del siguiente mes = último día
            break;
          case 'mes-pasado':
            desde = new Date(anio, mes - 1, 1);
            hasta = new Date(anio, mes, 0);
            break;
          case '30':
            desde = new Date(anio, mes, ahora.getDate() - 29);
            hasta = ahora;
            break;
          case 'anio':
            desde = new Date(anio, 0, 1);
            hasta = new Date(anio, 11, 31);
            break;
          default:
            return;
        }

        filtroDesde.value = aISO(desde);
        filtroHasta.value = aISO(hasta);
        formFiltros.submit();
      });
    });

    // Marcar el rango rápido que coincide con las fechas actuales
    document.querySelectorAll('[data-rango]').forEach((boton) => {
      const ahora = new Date();
      const rangos = {
        mes: [aISO(new Date(ahora.getFullYear(), ahora.getMonth(), 1)), aISO(new Date(ahora.getFullYear(), ahora.getMonth() + 1, 0))],
        'mes-pasado': [aISO(new Date(ahora.getFullYear(), ahora.getMonth() - 1, 1)), aISO(new Date(ahora.getFullYear(), ahora.getMonth(), 0))],
        30: [aISO(new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate() - 29)), aISO(ahora)],
        anio: [aISO(new Date(ahora.getFullYear(), 0, 1)), aISO(new Date(ahora.getFullYear(), 11, 31))],
      };
      const [d, h] = rangos[boton.dataset.rango];
      boton.classList.toggle('activo', filtroDesde.value === d && filtroHasta.value === h);
    });
  }


  /* ===================================================
     6. UTILIDADES COMPARTIDAS (módulo 5)
     =================================================== */
  const simbolo = window.SIMBOLO_DIVISA || '$';

  function formatoDinero(cantidad) {
    const signo = cantidad < 0 ? '-' : '';
    return signo + simbolo + Math.abs(cantidad).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  /**
   * Barras de progreso reactivas.
   * Lee data-actual y data-limite, calcula el porcentaje, anima el ancho
   * y cambia el color según el modo:
   *   presupuesto: verde (< 80 %), amarillo (>= 80 %), rojo (>= 100 %)
   *   meta:        azul (< 100 %), verde (>= 100 %)
   */
  function pintarProgreso(contenedor) {
    const actual = Number(contenedor.dataset.actual) || 0;
    const limite = Number(contenedor.dataset.limite) || 0;
    const modo = contenedor.dataset.modo || 'presupuesto';
    const porcentaje = limite > 0 ? (actual / limite) * 100 : 0;

    const barra = contenedor.querySelector('.barra-progreso');
    const relleno = contenedor.querySelector('.barra-relleno');
    const texto = contenedor.querySelector('.progreso-porcentaje');

    let estado;
    if (modo === 'meta') {
      estado = porcentaje >= 100 ? 'completo' : 'avance';
    } else if (porcentaje >= 100) {
      estado = 'excedido';
    } else if (porcentaje >= 80) {
      estado = 'alerta';
    } else {
      estado = 'normal';
    }

    contenedor.classList.remove('estado-normal', 'estado-alerta', 'estado-excedido', 'estado-avance', 'estado-completo');
    contenedor.classList.add(`estado-${estado}`);

    // Pequeña espera: primero se pinta en 0 y luego crece (transición CSS)
    setTimeout(() => {
      relleno.style.width = `${Math.min(porcentaje, 100)}%`;
    }, 50);
    texto.textContent = `${Math.round(porcentaje)}%`;
    barra.setAttribute('aria-valuenow', Math.round(Math.min(porcentaje, 100)));
  }

  document.querySelectorAll('.progreso').forEach(pintarProgreso);

  // Botones "Cancelar" de cualquier modal y clic fuera del formulario
  document.querySelectorAll('[data-cerrar-modal]').forEach((boton) => {
    boton.addEventListener('click', () => boton.closest('dialog').close());
  });
  document.querySelectorAll('dialog.modal').forEach((dialogo) => {
    dialogo.addEventListener('click', (event) => {
      if (event.target === dialogo) dialogo.close();
    });
  });

  // Formularios de eliminar que piden confirmación (texto en data-confirmar)
  document.querySelectorAll('.form-confirmar').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!confirm(form.dataset.confirmar)) event.preventDefault();
    });
  });


  /* ===================================================
     7. PRESUPUESTOS (presupuestos.php)
     =================================================== */
  const modalPresupuesto = document.getElementById('modal-presupuesto');

  if (modalPresupuesto) {
    const formPres = document.getElementById('form-presupuesto');
    const campoAccionP = document.getElementById('pres-accion');
    const campoIdP = document.getElementById('pres-id');
    const grupoCategoria = document.getElementById('grupo-pres-categoria');
    const campoCategoriaP = document.getElementById('pres-categoria');
    const nombreFijo = document.getElementById('pres-nombre-fijo');
    const campoLimite = document.getElementById('pres-limite');
    const textoGastado = document.getElementById('pres-gastado-texto');
    const previa = document.getElementById('pres-previa');
    const errorPres = document.getElementById('error-presupuesto');
    const tituloPres = document.getElementById('modal-pres-titulo');
    let gastadoActual = 0;

    // Vista previa: la barra reacciona mientras se escribe el límite
    function actualizarPreviaPresupuesto() {
      textoGastado.textContent = formatoDinero(gastadoActual);
      previa.dataset.actual = gastadoActual;
      previa.dataset.limite = Number(campoLimite.value) || 0;
      pintarProgreso(previa);
    }

    function gastadoDeCategoria() {
      const opcion = campoCategoriaP.selectedOptions[0];
      return opcion ? Number(opcion.dataset.gastado) : 0;
    }

    function abrirModalPresupuesto(datos) {
      const editando = Boolean(datos);
      formPres.reset();
      errorPres.textContent = '';

      campoAccionP.value = editando ? 'editar' : 'crear';
      campoIdP.value = editando ? datos.id : '0';
      tituloPres.textContent = editando ? 'Editar presupuesto' : 'Nuevo presupuesto';

      // Al editar, la categoría no cambia: solo el límite
      grupoCategoria.classList.toggle('oculto', editando);
      campoCategoriaP.disabled = editando;
      nombreFijo.classList.toggle('oculto', !editando);

      if (editando) {
        nombreFijo.textContent = datos.nombre;
        campoLimite.value = datos.limite;
        gastadoActual = Number(datos.gastado);
      } else {
        gastadoActual = gastadoDeCategoria();
      }

      actualizarPreviaPresupuesto();
      modalPresupuesto.showModal();
      campoLimite.focus();
    }

    const btnNuevoPres = document.getElementById('btn-nuevo-presupuesto');
    if (btnNuevoPres) btnNuevoPres.addEventListener('click', () => abrirModalPresupuesto(null));

    document.querySelectorAll('.btn-editar-presupuesto').forEach((boton) => {
      boton.addEventListener('click', () => abrirModalPresupuesto(boton.dataset));
    });

    campoCategoriaP.addEventListener('change', () => {
      gastadoActual = gastadoDeCategoria();
      actualizarPreviaPresupuesto();
    });
    campoLimite.addEventListener('input', actualizarPreviaPresupuesto);

    formPres.addEventListener('submit', (event) => {
      if (!(Number(campoLimite.value) > 0) || !/^\d+(\.\d{1,2})?$/.test(campoLimite.value)) {
        event.preventDefault();
        errorPres.textContent = 'Escribe un límite mayor a 0 con máximo 2 decimales.';
      }
    });
  }


  /* ===================================================
     8. METAS DE AHORRO (metas.php)
     =================================================== */
  const modalMeta = document.getElementById('modal-meta');

  if (modalMeta) {
    const formMeta = document.getElementById('form-meta');
    const campoAccionM = document.getElementById('meta-accion');
    const campoIdM = document.getElementById('meta-id');
    const campoNombreM = document.getElementById('meta-nombre');
    const campoObjetivo = document.getElementById('meta-objetivo');
    const campoFechaM = document.getElementById('meta-fecha');
    const campoInicial = document.getElementById('meta-inicial');
    const grupoInicial = document.getElementById('grupo-meta-inicial');
    const calculoMeta = document.getElementById('calculo-meta');
    const errorMeta = document.getElementById('error-meta');
    const tituloMeta = document.getElementById('modal-meta-titulo');
    const hoyMeta = campoFechaM.min;

    // Calcula en vivo cuánto hay que ahorrar al mes para llegar a la meta
    function calcularAhorroSugerido() {
      const objetivo = Number(campoObjetivo.value) || 0;
      const inicial = campoAccionM.value === 'crear' ? Number(campoInicial.value) || 0 : 0;
      if (!objetivo || !campoFechaM.value) {
        calculoMeta.textContent = '';
        return;
      }
      const dias = Math.ceil((new Date(`${campoFechaM.value}T00:00`) - new Date(`${hoyMeta}T00:00`)) / 86400000);
      const meses = Math.max(1, Math.ceil(dias / 30));
      const faltante = Math.max(0, objetivo - inicial);
      calculoMeta.textContent = faltante === 0
        ? '¡Con el ahorro inicial ya alcanzas la meta!'
        : `Necesitas ahorrar ${formatoDinero(faltante / meses)} al mes durante ${meses} mes(es).`;
    }

    [campoObjetivo, campoFechaM, campoInicial].forEach((campo) => {
      campo.addEventListener('input', calcularAhorroSugerido);
    });

    function abrirModalMeta(datos) {
      const editando = Boolean(datos);
      formMeta.reset();
      errorMeta.textContent = '';

      campoAccionM.value = editando ? 'editar' : 'crear';
      campoIdM.value = editando ? datos.id : '0';
      tituloMeta.textContent = editando ? 'Editar meta' : 'Nueva meta';
      grupoInicial.classList.toggle('oculto', editando);
      // Al editar se permite conservar una fecha que ya pasó
      campoFechaM.min = editando ? '' : hoyMeta;

      if (editando) {
        campoNombreM.value = datos.nombre;
        campoObjetivo.value = datos.objetivo;
        campoFechaM.value = datos.fecha;
      }

      calcularAhorroSugerido();
      modalMeta.showModal();
      campoNombreM.focus();
    }

    document.getElementById('btn-nueva-meta').addEventListener('click', () => abrirModalMeta(null));
    document.querySelectorAll('.btn-editar-meta').forEach((boton) => {
      boton.addEventListener('click', () => abrirModalMeta(boton.dataset));
    });

    formMeta.addEventListener('submit', (event) => {
      let mensaje = '';
      if (!campoNombreM.value.trim()) {
        mensaje = 'Escribe un nombre para la meta.';
      } else if (!(Number(campoObjetivo.value) > 0)) {
        mensaje = 'El objetivo debe ser mayor a 0.';
      } else if (!campoFechaM.value) {
        mensaje = 'Selecciona una fecha límite.';
      } else if (campoAccionM.value === 'crear' && campoFechaM.value < hoyMeta) {
        mensaje = 'La fecha límite no puede estar en el pasado.';
      }
      if (mensaje) {
        event.preventDefault();
        errorMeta.textContent = mensaje;
      }
    });

    // --- Abonar / retirar con vista previa del progreso ---
    const modalMov = document.getElementById('modal-movimiento-meta');
    const campoAccionMov = document.getElementById('mov-accion');
    const campoIdMov = document.getElementById('mov-id');
    const campoMontoMov = document.getElementById('mov-monto');
    const previaMov = document.getElementById('mov-previa');
    const errorMov = document.getElementById('error-mov');
    let metaActual = { actual: 0, objetivo: 1, accion: 'abonar' };

    function actualizarPreviaMeta() {
      const monto = Number(campoMontoMov.value) || 0;
      const nuevo = metaActual.accion === 'abonar' ? metaActual.actual + monto : metaActual.actual - monto;
      previaMov.dataset.actual = Math.max(0, nuevo);
      previaMov.dataset.limite = metaActual.objetivo;
      pintarProgreso(previaMov);
      errorMov.textContent = nuevo < 0 ? `Solo tienes ${formatoDinero(metaActual.actual)} ahorrados.` : '';
    }

    document.querySelectorAll('.btn-movimiento-meta').forEach((boton) => {
      boton.addEventListener('click', () => {
        const abonar = boton.dataset.accion === 'abonar';
        metaActual = {
          actual: Number(boton.dataset.actual),
          objetivo: Number(boton.dataset.objetivo),
          accion: boton.dataset.accion,
        };
        campoAccionMov.value = boton.dataset.accion;
        campoIdMov.value = boton.dataset.id;
        campoMontoMov.value = '';
        document.getElementById('mov-titulo').textContent = abonar ? 'Abonar a la meta' : 'Retirar de la meta';
        document.getElementById('mov-nombre').textContent = boton.dataset.nombre;
        document.getElementById('mov-guardar').textContent = abonar ? 'Abonar' : 'Retirar';
        actualizarPreviaMeta();
        modalMov.showModal();
        campoMontoMov.focus();
      });
    });

    campoMontoMov.addEventListener('input', actualizarPreviaMeta);

    document.getElementById('form-movimiento-meta').addEventListener('submit', (event) => {
      const monto = Number(campoMontoMov.value);
      if (!(monto > 0)) {
        event.preventDefault();
        errorMov.textContent = 'Escribe un monto mayor a 0.';
      } else if (metaActual.accion === 'retirar' && monto > metaActual.actual) {
        event.preventDefault();
        errorMov.textContent = `Solo tienes ${formatoDinero(metaActual.actual)} ahorrados.`;
      }
    });
  }


  /* ===================================================
     9. GRÁFICAS DEL PANEL (dashboard.php) - sin librerías
     =================================================== */
  const datosGraficas = document.getElementById('datos-graficas');

  if (datosGraficas) {
    const datos = JSON.parse(datosGraficas.textContent);
    const simboloPanel = datos.simbolo;
    const dinero = (n) => simboloPanel + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // --- Gráfica de dona: gastos por categoría (conic-gradient) ---
    const contenedorDona = document.getElementById('grafica-dona');
    const total = datos.categorias.reduce((suma, c) => suma + c.total, 0);

    if (total > 0) {
      let acumulado = 0;
      const segmentos = datos.categorias.map((c) => {
        const inicio = (acumulado / total) * 100;
        acumulado += c.total;
        const fin = (acumulado / total) * 100;
        return `${c.color} ${inicio}% ${fin}%`;
      });

      const leyenda = datos.categorias.map((c) => `
        <li>
          <i class="punto" style="background:${c.color}"></i>
          <span class="leyenda-nombre"></span>
          <strong>${dinero(c.total)}</strong>
          <span class="texto-tenue">${Math.round((c.total / total) * 100)}%</span>
        </li>`).join('');

      contenedorDona.innerHTML = `
        <div class="dona" style="background: conic-gradient(${segmentos.join(', ')})">
          <div class="dona-centro"><span>Total</span><strong>${dinero(total)}</strong></div>
        </div>
        <ul class="leyenda-dona">${leyenda}</ul>`;

      // Los nombres se asignan con textContent (no como HTML) para evitar XSS
      contenedorDona.querySelectorAll('.leyenda-nombre').forEach((span, i) => {
        span.textContent = datos.categorias[i].nombre;
      });
    }

    // --- Gráfica de barras: ingresos vs gastos por mes ---
    const contenedorBarras = document.getElementById('grafica-barras');
    const maximo = Math.max(1, ...datos.historial.flatMap((m) => [m.ingresos, m.gastos]));

    datos.historial.forEach((mes) => {
      const columna = document.createElement('div');
      columna.className = 'columna-mes';
      columna.innerHTML = `
        <div class="barras-par">
          <div class="barra-vertical barra-ingreso" title="Ingresos: ${dinero(mes.ingresos)}"></div>
          <div class="barra-vertical barra-gasto" title="Gastos: ${dinero(mes.gastos)}"></div>
        </div>
        <span class="etiqueta-mes">${mes.etiqueta}</span>`;
      contenedorBarras.appendChild(columna);

      const [barraIngreso, barraGasto] = columna.querySelectorAll('.barra-vertical');
      setTimeout(() => {
        barraIngreso.style.height = `${(mes.ingresos / maximo) * 100}%`;
        barraGasto.style.height = `${(mes.gastos / maximo) * 100}%`;
      }, 50);
    });
  }


  /* ===================================================
     10. ADMINISTRACIÓN: catálogos y usuarios
     =================================================== */

  // Modal genérico: el botón trae en data-valores (JSON) los datos a precargar
  document.querySelectorAll('.btn-abrir-modal').forEach((boton) => {
    boton.addEventListener('click', () => {
      const modal = document.getElementById(boton.dataset.modal);
      const form = modal.querySelector('form');
      const valores = JSON.parse(boton.dataset.valores || '{}');

      form.reset();
      form.elements.accion.value = boton.dataset.accion;
      modal.querySelector('.titulo-modal').textContent = boton.dataset.titulo;
      Object.entries(valores).forEach(([campo, valor]) => {
        if (form.elements[campo]) form.elements[campo].value = valor;
      });

      form.dispatchEvent(new Event('input')); // refresca vistas previas
      modal.showModal();
      const primerCampo = form.querySelector('input:not([type="hidden"])');
      if (primerCampo) primerCampo.focus();
    });
  });

  // Divisas: código siempre en mayúsculas y ejemplo del formato en vivo
  const campoCodigo = document.getElementById('divisa-codigo');
  if (campoCodigo) {
    const campoSimbolo = document.getElementById('divisa-simbolo');
    const ejemplo = document.getElementById('divisa-ejemplo');
    const formDivisa = campoCodigo.form;

    formDivisa.addEventListener('input', () => {
      campoCodigo.value = campoCodigo.value.toUpperCase().replace(/[^A-Z]/g, '');
      ejemplo.textContent = `${campoSimbolo.value || '$'}1,234.50 ${campoCodigo.value}`;
    });
  }

  // Usuarios: filtro por estado y búsqueda por nombre o correo
  const tablaUsuarios = document.getElementById('tabla-usuarios');
  if (tablaUsuarios) {
    const filas = tablaUsuarios.querySelectorAll('tbody tr[data-estado]');
    const filaVacia = document.getElementById('fila-sin-usuarios');
    const buscarUsuario = document.getElementById('buscar-usuario');
    const botonesEstado = document.querySelectorAll('[data-filtro-estado]');
    let estadoActual = 'todos';

    function filtrarUsuarios() {
      const texto = buscarUsuario.value.trim().toLowerCase();
      let visibles = 0;
      filas.forEach((fila) => {
        const mostrar = (estadoActual === 'todos' || fila.dataset.estado === estadoActual)
          && fila.dataset.busqueda.includes(texto);
        fila.classList.toggle('oculto', !mostrar);
        if (mostrar) visibles++;
      });
      filaVacia.classList.toggle('oculto', visibles > 0);
    }

    botonesEstado.forEach((boton) => {
      boton.addEventListener('click', () => {
        botonesEstado.forEach((b) => b.classList.remove('activo'));
        boton.classList.add('activo');
        estadoActual = boton.dataset.filtroEstado;
        filtrarUsuarios();
      });
    });
    buscarUsuario.addEventListener('input', filtrarUsuarios);
  }


  /* ===================================================
     11. PERFIL DE USUARIO (perfil.php)
     =================================================== */
  const formDatos = document.getElementById('form-datos');

  if (formDatos) {
    const campoCorreoP = document.getElementById('perfil-correo');
    const campoDivisaP = document.getElementById('perfil-divisa');
    const grupoConfirmar = document.getElementById('grupo-confirmar-correo');
    const campoConfirmarCorreo = document.getElementById('perfil-password-confirmacion');
    const notaDivisa = document.getElementById('nota-divisa');
    const btnGuardarDatos = document.getElementById('btn-guardar-datos');
    const errorDatos = document.getElementById('error-datos');
    const camposDatos = formDatos.querySelectorAll('input[type="text"], input[type="email"], select');
    const patronCorreoP = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // ¿Algún campo es distinto a su valor original? Solo entonces se puede guardar
    function revisarCambios() {
      const hayCambios = [...camposDatos].some((campo) => (campo.tagName === 'SELECT'
        ? campo.value !== campo.dataset.original
        : campo.value.trim() !== campo.defaultValue));

      const correoCambio = campoCorreoP.value.trim().toLowerCase() !== campoCorreoP.dataset.original;
      grupoConfirmar.classList.toggle('oculto', !correoCambio);
      notaDivisa.classList.toggle('oculto', campoDivisaP.value === campoDivisaP.dataset.original);
      btnGuardarDatos.disabled = !hayCambios;
      errorDatos.textContent = '';
    }

    formDatos.addEventListener('input', revisarCambios);
    formDatos.addEventListener('change', revisarCambios);
    // "Descartar cambios": el reset ocurre después del evento, por eso la pequeña espera
    formDatos.addEventListener('reset', () => setTimeout(revisarCambios, 0));

    formDatos.addEventListener('submit', (event) => {
      let mensaje = '';
      const nombre = document.getElementById('perfil-nombre').value.trim();
      const apellidos = document.getElementById('perfil-apellidos').value.trim();

      if (!nombre || !apellidos) {
        mensaje = 'El nombre y los apellidos son obligatorios.';
      } else if (!patronCorreoP.test(campoCorreoP.value.trim())) {
        mensaje = 'Escribe un correo electrónico válido.';
      } else if (!grupoConfirmar.classList.contains('oculto') && !campoConfirmarCorreo.value) {
        mensaje = 'Escribe tu contraseña actual para cambiar el correo.';
      }

      if (mensaje) {
        event.preventDefault();
        errorDatos.textContent = mensaje;
      }
    });
  }

  const formPassword = document.getElementById('form-password');

  if (formPassword) {
    const campoActual = document.getElementById('password-actual');
    const campoNueva = document.getElementById('password-nueva');
    const campoConfirmarP = document.getElementById('password-confirmar');
    const medidor = document.getElementById('medidor-password');
    const textoMedidor = medidor.querySelector('.medidor-texto');
    const rellenoMedidor = medidor.querySelector('.medidor-relleno');
    const textoCoincidencia = document.getElementById('coincidencia-password');
    const errorPass = document.getElementById('error-password-perfil');

    // Medidor de seguridad: suma puntos por longitud y variedad de caracteres
    const niveles = [
      { texto: 'Mínimo 6 caracteres', clase: 'nivel-0' },
      { texto: 'Muy débil', clase: 'nivel-1' },
      { texto: 'Débil', clase: 'nivel-2' },
      { texto: 'Aceptable', clase: 'nivel-3' },
      { texto: 'Fuerte', clase: 'nivel-4' },
      { texto: 'Muy fuerte', clase: 'nivel-5' },
    ];

    function puntajePassword(password) {
      if (password.length < 6) return 0;
      let puntos = 1;
      if (password.length >= 10) puntos++;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) puntos++;
      if (/\d/.test(password)) puntos++;
      if (/[^A-Za-z0-9]/.test(password)) puntos++;
      return puntos;
    }

    function actualizarMedidor() {
      const puntos = campoNueva.value ? puntajePassword(campoNueva.value) : 0;
      const nivel = niveles[puntos];
      medidor.className = `medidor-password ${nivel.clase}`;
      rellenoMedidor.style.width = `${(puntos / 5) * 100}%`;
      textoMedidor.textContent = campoNueva.value ? nivel.texto : niveles[0].texto;
    }

    function actualizarCoincidencia() {
      if (!campoConfirmarP.value) {
        textoCoincidencia.textContent = '';
        return;
      }
      const coinciden = campoNueva.value === campoConfirmarP.value;
      textoCoincidencia.textContent = coinciden ? '✔ Las contraseñas coinciden' : '✖ Las contraseñas no coinciden';
      textoCoincidencia.className = `texto-coincidencia ${coinciden ? 'texto-exito' : 'texto-alerta'}`;
    }

    campoNueva.addEventListener('input', () => {
      actualizarMedidor();
      actualizarCoincidencia();
    });
    campoConfirmarP.addEventListener('input', actualizarCoincidencia);

    // Botones para mostrar u ocultar la contraseña
    formPassword.querySelectorAll('.btn-ver-password').forEach((boton) => {
      boton.addEventListener('click', () => {
        const campo = document.getElementById(boton.dataset.ver);
        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        boton.textContent = mostrar ? '🙈' : '👁️';
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
      });
    });

    formPassword.addEventListener('submit', (event) => {
      let mensaje = '';
      if (!campoActual.value) {
        mensaje = 'Escribe tu contraseña actual.';
      } else if (campoNueva.value.length < 6) {
        mensaje = 'La nueva contraseña debe tener al menos 6 caracteres.';
      } else if (campoNueva.value !== campoConfirmarP.value) {
        mensaje = 'La nueva contraseña y su confirmación no coinciden.';
      } else if (campoNueva.value === campoActual.value) {
        mensaje = 'La nueva contraseña debe ser diferente a la actual.';
      }

      if (mensaje) {
        event.preventDefault();
        errorPass.textContent = mensaje;
      }
    });
  }


  /* ===================================================
     12. MENSAJES EMERGENTES (toast) - se ocultan solos
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
