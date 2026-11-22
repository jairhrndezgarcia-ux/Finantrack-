<?php
// index.php - Página principal (Módulo 1: Home y Presentación)
require_once 'includes/auth.php';
require_once 'config/conexion.php';

// Divisas activas para el formulario de registro (catálogo administrado por el admin)
$divisas = mysqli_query($conexion, 'SELECT codigo, nombre FROM divisas WHERE activo = 1 ORDER BY id');

$titulo = 'Gestor Web de Finanzas Personales';
require 'includes/header.php';
?>

  <main>
    <!-- Sección de Bienvenida (Hero Section) -->
    <section id="inicio" class="hero-section">
      <div class="hero-contenido">
        <h2>Toma el control total de tus Finanzas Personales</h2>
        <p>Administra tus presupuestos mensuales, clasifica ingresos y gastos, y alcanza tus metas de ahorro fácilmente.</p>
        <?php if (usuario_autenticado()): ?>
          <a href="<?= ruta_inicio() ?>" class="btn-primario">Ir a mi panel</a>
        <?php else: ?>
          <a href="#registro" class="btn-primario">Comenzar Ahora</a>
        <?php endif; ?>
      </div>
    </section>

    <!-- Resumen de Funcionalidades (Diseño Adaptativo en Columnas) -->
    <section id="funciones" class="seccion-padding">
      <h2 class="titulo-seccion">¿Por qué elegir FinanTrack?</h2>
      <div class="contenedor-grid">
        <article class="tarjeta-funcion">
          <div class="icono-tarjeta">📊</div>
          <h3>Control de Flujos</h3>
          <p>Registra y clasifica tus ingresos y gastos diarios en categorías personalizadas.</p>
        </article>

        <article class="tarjeta-funcion">
          <div class="icono-tarjeta">🎯</div>
          <h3>Presupuestos Inteligentes</h3>
          <p>Fija límites por categoría y recibe alertas visuales reactivas al alcanzar el 80% o 100%.</p>
        </article>

        <article class="tarjeta-funcion">
          <div class="icono-tarjeta">💡</div>
          <h3>Metas de Ahorro</h3>
          <p>Establece metas financieras con fecha límite y monitorea tu progreso en tiempo real.</p>
        </article>
      </div>
    </section>

    <!-- Herramienta de Simulación / Calculadora Interactiva (JS) -->
    <section id="calculadora" class="seccion-padding bg-alt">
      <h2 class="titulo-seccion">Calculadora Interactiva de Ahorro</h2>
      <p class="subtitulo-seccion">Estima tu capacidad de ahorro mensual antes de registrarte.</p>

      <div class="contenedor-calculadora">
        <form id="form-calculadora" class="form-estilizado" onsubmit="event.preventDefault();">
          <div class="campo-grupo">
            <label for="calc-ingreso">Ingreso Mensual ($):</label>
            <input type="number" id="calc-ingreso" placeholder="Ej. 15000" min="0" required>
          </div>

          <div class="campo-grupo">
            <label for="calc-gasto">Gastos Fijos Estimados ($):</label>
            <input type="number" id="calc-gasto" placeholder="Ej. 9000" min="0" required>
          </div>

          <button type="button" id="btn-calcular" class="btn-primario">Calcular Ahorro</button>
        </form>

        <div id="resultado-calculadora" class="resultado-caja oculto">
          <h3>Capacidad Estimada de Ahorro:</h3>
          <p class="monto-ahorro" id="monto-resultado">$0.00</p>
          <p id="mensaje-resultado" class="mensaje-resultado"></p>
        </div>
      </div>
    </section>

    <?php if (!usuario_autenticado()): ?>
    <!-- Formulario de Registro Validado en Tiempo Real (Módulo 2) -->
    <section id="registro" class="seccion-padding">
      <h2 class="titulo-seccion">Crea tu Cuenta Gratis</h2>

      <div class="contenedor-formulario">
        <form id="form-registro" class="form-estilizado" action="registro.php" method="POST">
          <div class="campo-grupo">
            <label for="reg-nombre">Nombre(s):</label>
            <input type="text" id="reg-nombre" name="nombre" placeholder="Tu nombre" maxlength="60" required>
          </div>

          <div class="campo-grupo">
            <label for="reg-apellidos">Apellidos:</label>
            <input type="text" id="reg-apellidos" name="apellidos" placeholder="Tus apellidos" maxlength="80" required>
          </div>

          <div class="campo-grupo">
            <label for="reg-correo">Correo Electrónico:</label>
            <input type="email" id="reg-correo" name="correo" placeholder="correo@ejemplo.com" maxlength="120" required>
          </div>

          <div class="campo-grupo">
            <label for="reg-divisa">Divisa Base:</label>
            <select id="reg-divisa" name="divisa" required>
              <?php while ($divisa = mysqli_fetch_assoc($divisas)): ?>
                <option value="<?= e($divisa['codigo']) ?>">
                  <?= e($divisa['nombre']) ?> (<?= e($divisa['codigo']) ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="campo-grupo">
            <label for="reg-password">Contraseña (Mínimo 6 caracteres):</label>
            <input type="password" id="reg-password" name="password" placeholder="******" required>
          </div>

          <div class="campo-grupo">
            <label for="reg-confirmar">Confirmar Contraseña:</label>
            <input type="password" id="reg-confirmar" name="confirmar_password" placeholder="******" required>
            <span id="error-password" class="texto-error"></span>
          </div>

          <button type="submit" class="btn-primario btn-bloque">Registrar Cuenta</button>
          <p class="texto-enlace">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </form>
      </div>
    </section>
    <?php endif; ?>
  </main>

<?php require 'includes/footer.php'; ?>
