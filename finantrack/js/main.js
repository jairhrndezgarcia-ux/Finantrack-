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
     4. MENSAJES EMERGENTES (toast) - se ocultan solos
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
