<?php
// index.php - Página principal (Módulo 1: Home y Presentación)
require_once 'includes/auth.php';

// Con sesión iniciada, la página de inicio es su panel
if (usuario_autenticado()) {
    redirigir(ruta_inicio());
}

require_once 'config/conexion.php';

// Divisas activas para el formulario de registro (catálogo administrado por el admin)
$divisas = mysqli_query($conexion, 'SELECT codigo, nombre FROM divisas WHERE activo = 1 ORDER BY id');

$titulo = 'Gestor Web de Finanzas Personales';
require 'includes/header.php';
?>

  <main>
    <!-- ===== Hero: el mes en curso con presupuestos que se llenan ===== -->
    <section class="hero" id="inicio">
      <div class="landing-contenedor">
        <div class="hero-texto">
          <h1>Sabe a dónde va cada peso antes de que termine el mes.</h1>
          <p>Registra tus ingresos y gastos, ponle un límite a cada categoría y recibe un aviso cuando estés por pasarte. Sin hojas de cálculo.</p>
          <div class="hero-acciones">
            <a href="#registro" class="btn-primario">Crear cuenta gratis</a>
            <a href="#calculadora" class="btn-claro">Calcular mi ahorro</a>
          </div>
        </div>

        <!-- Vista de ejemplo del panel (datos ilustrativos) -->
        <div class="mes-en-curso" aria-label="Ejemplo de presupuestos de un mes">
          <div class="mes-encabezado">
            <h2>Noviembre</h2>
            <span>Día 22 de 30</span>
          </div>
          <p class="mes-disponible">$7,100.00</p>
          <p class="mes-disponible-etiqueta">Disponible este mes</p>

          <div class="mes-fila">
            <div class="mes-fila-texto"><span>🍽️ Alimentación</span><strong>$2,480 de $4,000</strong></div>
            <div class="mes-barra"><i style="--avance: 62%"></i></div>
          </div>
          <div class="mes-fila en-alerta">
            <div class="mes-fila-texto"><span>🎬 Salidas</span><strong>$1,290 de $1,500</strong></div>
            <div class="mes-barra en-alerta"><i style="--avance: 86%; --retraso: .15s"></i></div>
          </div>
          <div class="mes-fila en-exceso">
            <div class="mes-fila-texto"><span>🚌 Transporte</span><strong>$1,560 de $1,500</strong></div>
            <div class="mes-barra en-exceso"><i style="--avance: 100%; --retraso: .3s"></i></div>
          </div>

          <p class="mes-aviso">Transporte superó su límite por $60. Salidas va en 86 %.</p>
        </div>
      </div>
    </section>

    <!-- ===== Funciones ===== -->
    <section class="landing-seccion" id="funciones">
      <div class="landing-contenedor">
        <h2 class="landing-titulo">Todo tu dinero en un solo lugar</h2>
        <p class="landing-intro">FinanTrack reúne lo que normalmente anotas en libretas, notas del celular y estados de cuenta.</p>

        <div class="funciones">
          <article class="funcion">
            <div class="funcion-icono" aria-hidden="true">💸</div>
            <h3>Movimientos</h3>
            <p>Anota cada ingreso y gasto con su categoría, fecha y método de pago. Filtra por mes o por categoría.</p>
          </article>
          <article class="funcion">
            <div class="funcion-icono" aria-hidden="true">🎯</div>
            <h3>Presupuestos con aviso</h3>
            <p>Ponle tope a cada categoría. La barra se pone amarilla al 80 % y roja cuando te pasas.</p>
          </article>
          <article class="funcion">
            <div class="funcion-icono" aria-hidden="true">🐷</div>
            <h3>Metas de ahorro</h3>
            <p>Define cuánto quieres juntar y para cuándo. Te decimos cuánto apartar cada mes.</p>
          </article>
          <article class="funcion">
            <div class="funcion-icono" aria-hidden="true">📊</div>
            <h3>Resumen visual</h3>
            <p>Mira en qué se te va el dinero y cómo cambian tus ingresos y gastos de los últimos seis meses.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- ===== Calculadora interactiva (JavaScript) ===== -->
    <section class="landing-seccion seccion-blanca" id="calculadora">
      <div class="landing-contenedor calculadora-grid">
        <div>
          <h2 class="landing-titulo">¿Cuánto puedes ahorrar al mes?</h2>
          <p class="landing-intro">Escribe lo que ganas y lo que gastas en cosas fijas (renta, servicios, transporte). El cálculo se hace en tu navegador; no se guarda nada.</p>
        </div>

        <div>
          <form id="form-calculadora" class="form-estilizado" onsubmit="event.preventDefault();">
            <div class="campo-grupo">
              <label for="calc-ingreso">Ingreso mensual ($)</label>
              <input type="number" id="calc-ingreso" placeholder="15000" min="0" required>
            </div>

            <div class="campo-grupo">
              <label for="calc-gasto">Gastos fijos al mes ($)</label>
              <input type="number" id="calc-gasto" placeholder="9000" min="0" required>
            </div>

            <button type="button" id="btn-calcular" class="btn-primario btn-bloque">Calcular ahorro</button>

            <div id="resultado-calculadora" class="resultado-caja oculto" aria-live="polite">
              <h3>Podrías ahorrar al mes</h3>
              <p class="monto-ahorro" id="monto-resultado">$0.00</p>
              <p id="mensaje-resultado" class="mensaje-resultado"></p>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- ===== Buenas prácticas financieras ===== -->
    <section class="landing-seccion" id="consejos">
      <div class="landing-contenedor">
        <h2 class="landing-titulo">Buenas prácticas para tu dinero</h2>
        <p class="landing-intro">No hace falta ser experto. Estos hábitos funcionan con cualquier ingreso.</p>

        <div class="consejos-grid">
          <div>
            <h3 class="regla-titulo">La regla 50/30/20</h3>
            <p class="mensaje-resultado">Una forma sencilla de repartir lo que ganas cada mes.</p>

            <div class="regla-barra" role="img" aria-label="50 por ciento necesidades, 30 por ciento deseos, 20 por ciento ahorro">
              <span class="regla-necesidades">50 %</span>
              <span class="regla-deseos">30 %</span>
              <span class="regla-ahorro">20 %</span>
            </div>
            <div class="regla-leyenda">
              <p><strong>Necesidades</strong>Renta, comida, servicios, transporte.</p>
              <p><strong>Deseos</strong>Salidas, ropa, suscripciones.</p>
              <p><strong>Ahorro</strong>Metas, fondo de emergencia, deudas.</p>
            </div>
          </div>

          <ul class="lista-consejos">
            <li>
              <h3>Arma un fondo de emergencia</h3>
              <p>Junta entre 3 y 6 meses de tus gastos básicos para imprevistos como una enfermedad o perder el trabajo.</p>
            </li>
            <li>
              <h3>Págate primero</h3>
              <p>Aparta tu ahorro el día que recibes tu ingreso, no con lo que sobre a fin de mes.</p>
            </li>
            <li>
              <h3>Cuida los gastos hormiga</h3>
              <p>Un café o un antojo diario parece poco, pero en un mes puede sumar más que un servicio.</p>
            </li>
            <li>
              <h3>Revisa tus números cada semana</h3>
              <p>Cinco minutos a la semana bastan para corregir antes de que el mes se te escape.</p>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- ===== Testimonios ===== -->
    <section class="landing-seccion seccion-blanca" id="testimonios">
      <div class="landing-contenedor">
        <h2 class="landing-titulo">Lo que dicen quienes lo usan</h2>
        <p class="landing-intro">Personas con ingresos y gastos muy distintos, con el mismo problema: no saber en qué se iba el dinero.</p>

        <div class="testimonios">
          <figure class="testimonio">
            <blockquote>“Pensaba que gastaba poco en comida fuera. La gráfica me dijo que era casi la mitad de mi mes.”</blockquote>
            <figcaption>
              <span class="avatar-mini" aria-hidden="true">M</span>
              <span><strong>Mariana R.</strong>Estudiante de ingeniería</span>
            </figcaption>
          </figure>
          <figure class="testimonio">
            <blockquote>“Como freelance mis ingresos cambian cada mes. Con los presupuestos ya no llego en ceros a la quincena.”</blockquote>
            <figcaption>
              <span class="avatar-mini" aria-hidden="true">L</span>
              <span><strong>Luis G.</strong>Diseñador independiente</span>
            </figcaption>
          </figure>
          <figure class="testimonio">
            <blockquote>“Junté para el enganche de mi coche en ocho meses. Ver la barra avanzar me ayudó a no rendirme.”</blockquote>
            <figcaption>
              <span class="avatar-mini" aria-hidden="true">A</span>
              <span><strong>Ana P.</strong>Docente de secundaria</span>
            </figcaption>
          </figure>
        </div>
        <p class="nota-ilustrativa">Testimonios ilustrativos creados para este proyecto escolar.</p>
      </div>
    </section>

    <!-- ===== Registro validado en tiempo real (Módulo 2) ===== -->
    <section class="landing-seccion" id="registro">
      <div class="landing-contenedor registro-grid">
        <div>
          <h2 class="landing-titulo">Crea tu cuenta</h2>
          <p class="landing-intro">Es gratis y tardas menos de un minuto.</p>
          <ul class="beneficios">
            <li>Categorías listas para empezar, y puedes crear las tuyas.</li>
            <li>Elige tu moneda: pesos, dólares o euros.</li>
            <li>Tus datos solo los ves tú; la contraseña se guarda cifrada.</li>
          </ul>
        </div>

        <form id="form-registro" class="form-estilizado" action="registro.php" method="POST">
          <div class="campo-fila">
            <div class="campo-grupo">
              <label for="reg-nombre">Nombre(s)</label>
              <input type="text" id="reg-nombre" name="nombre" maxlength="60" autocomplete="given-name" required>
            </div>
            <div class="campo-grupo">
              <label for="reg-apellidos">Apellidos</label>
              <input type="text" id="reg-apellidos" name="apellidos" maxlength="80" autocomplete="family-name" required>
            </div>
          </div>

          <div class="campo-grupo">
            <label for="reg-correo">Correo electrónico</label>
            <input type="email" id="reg-correo" name="correo" placeholder="nombre@correo.com" maxlength="120" autocomplete="email" required>
          </div>

          <div class="campo-grupo">
            <label for="reg-divisa">Moneda</label>
            <select id="reg-divisa" name="divisa" required>
              <?php while ($divisa = mysqli_fetch_assoc($divisas)): ?>
                <option value="<?= e($divisa['codigo']) ?>">
                  <?= e($divisa['nombre']) ?> (<?= e($divisa['codigo']) ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="campo-fila">
            <div class="campo-grupo">
              <label for="reg-password">Contraseña</label>
              <input type="password" id="reg-password" name="password" placeholder="Mínimo 6 caracteres" autocomplete="new-password" required>
            </div>
            <div class="campo-grupo">
              <label for="reg-confirmar">Confirmar contraseña</label>
              <input type="password" id="reg-confirmar" name="confirmar_password" autocomplete="new-password" required>
            </div>
          </div>
          <span id="error-password" class="texto-error" aria-live="polite"></span>

          <button type="submit" class="btn-primario btn-bloque">Crear cuenta</button>
          <p class="texto-enlace">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </form>
      </div>
    </section>
  </main>

<?php require 'includes/footer.php'; ?>
