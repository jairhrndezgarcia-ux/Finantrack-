<?php
// login.php - Inicio de sesión (Módulo 2)
require_once 'includes/auth.php';
require_once 'config/conexion.php';

// Si ya tiene sesión, mandarlo directo a su panel
if (usuario_autenticado()) {
    redirigir(ruta_inicio());
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = strtolower(trim($_POST['correo'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $error = 'Escribe tu correo y tu contraseña.';
    } else {
        try {
            // Consulta preparada: el correo nunca se concatena en el SQL
            $stmt = mysqli_prepare(
                $conexion,
                'SELECT id, nombre, correo, password_hash, rol_id, activo
                   FROM usuarios WHERE correo = ?'
            );
            mysqli_stmt_bind_param($stmt, 's', $correo);
            mysqli_stmt_execute($stmt);
            $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            // Mismo mensaje si el correo no existe o la contraseña es incorrecta,
            // para no revelar qué correos están registrados.
            if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
                $error = 'Correo o contraseña incorrectos.';
            } elseif ((int) $usuario['activo'] !== 1) {
                $error = 'Tu cuenta está desactivada. Contacta al administrador.';
            } else {
                // Registrar la fecha del último acceso (lo consulta el administrador)
                $stmt = mysqli_prepare($conexion, 'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?');
                $id   = (int) $usuario['id'];
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);

                iniciar_sesion_usuario($id, $usuario['nombre'], $usuario['correo'], (int) $usuario['rol_id']);
                flash('¡Bienvenido de nuevo, ' . $usuario['nombre'] . '!', 'exito');
                redirigir(ruta_inicio());
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Error en login: ' . $e->getMessage());
            $error = 'Ocurrió un error al iniciar sesión. Intenta más tarde.';
        }
    }
}

$titulo = 'Iniciar sesión';
require 'includes/header.php';
?>

  <main class="pagina-acceso">
    <div class="acceso-tarjeta">
      <!-- Panel de marca (se oculta en móvil) -->
      <div class="acceso-marca">
        <h2>Tu mes, bajo control.</h2>
        <p>Entra para ver tus movimientos, tus presupuestos y cuánto te falta para tus metas.</p>
        <div class="acceso-dato">
          <span>Ahorro de noviembre</span>
          <strong>$3,250.00</strong>
          <div class="mes-barra"><i style="--avance: 65%"></i></div>
          <small>65 % de la meta del mes</small>
        </div>
      </div>

      <form id="form-login" class="acceso-form" action="login.php" method="POST" novalidate>
        <h1>Iniciar sesión</h1>
        <p class="subtitulo-seccion">Usa el correo con el que te registraste.</p>

        <?php if ($error): ?>
          <p class="alerta alerta-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="campo-grupo">
          <label for="login-correo">Correo electrónico</label>
          <input type="email" id="login-correo" name="correo" value="<?= e($correo) ?>"
                 placeholder="nombre@correo.com" autocomplete="username" required autofocus>
        </div>

        <div class="campo-grupo">
          <label for="login-password">Contraseña</label>
          <input type="password" id="login-password" name="password"
                 autocomplete="current-password" required>
          <span id="error-login" class="texto-error" aria-live="polite"></span>
        </div>

        <button type="submit" class="btn-primario btn-bloque">Entrar</button>
        <p class="texto-enlace">¿No tienes cuenta? <a href="index.php#registro">Crea una gratis</a></p>
      </form>
    </div>
  </main>

<?php require 'includes/footer.php'; ?>
