<?php
// admin/index.php - Panel de administración (rol 1)
$base = '../';
require_once '../includes/auth.php';
requerir_admin($base);
require_once '../config/conexion.php';

// Totales de los catálogos y de la plataforma
$totales = mysqli_fetch_assoc(mysqli_query($conexion, '
    SELECT
      (SELECT COUNT(*) FROM usuarios WHERE rol_id = 2)                          AS usuarios,
      (SELECT COUNT(*) FROM usuarios WHERE rol_id = 2 AND activo = 0)           AS inactivos,
      (SELECT COUNT(*) FROM categorias WHERE usuario_id IS NULL AND activo = 1) AS categorias,
      (SELECT COUNT(*) FROM divisas WHERE activo = 1)                           AS divisas,
      (SELECT COUNT(*) FROM metodos_pago WHERE activo = 1)                      AS metodos,
      (SELECT COUNT(*) FROM transacciones WHERE activo = 1)                     AS movimientos
'));

// Últimos usuarios registrados
$recientes = mysqli_query($conexion, '
    SELECT nombre, apellidos, correo, activo, creado_en
      FROM usuarios WHERE rol_id = 2
     ORDER BY creado_en DESC LIMIT 5
');

$titulo = 'Panel de administración';
require '../includes/header.php';
?>

  <main class="seccion-padding">
    <h2 class="titulo-seccion">Panel de administración</h2>
    <p class="subtitulo-seccion">Gestión de catálogos globales y supervisión de cuentas.</p>

    <div class="grid-admin">
      <a href="usuarios.php" class="tarjeta-admin">
        <div class="icono-tarjeta">👥</div>
        <h3><?= (int) $totales['usuarios'] ?> usuarios</h3>
        <p><?= (int) $totales['inactivos'] ?> cuenta(s) inactiva(s)</p>
        <span class="enlace-admin">Gestionar cuentas →</span>
      </a>
      <a href="categorias.php" class="tarjeta-admin">
        <div class="icono-tarjeta">🏷️</div>
        <h3><?= (int) $totales['categorias'] ?> categorías</h3>
        <p>Categorías globales activas</p>
        <span class="enlace-admin">Administrar →</span>
      </a>
      <a href="metodos.php" class="tarjeta-admin">
        <div class="icono-tarjeta">💳</div>
        <h3><?= (int) $totales['metodos'] ?> métodos de pago</h3>
        <p>Métodos activos</p>
        <span class="enlace-admin">Administrar →</span>
      </a>
      <a href="divisas.php" class="tarjeta-admin">
        <div class="icono-tarjeta">💱</div>
        <h3><?= (int) $totales['divisas'] ?> divisas</h3>
        <p>Divisas disponibles en el registro</p>
        <span class="enlace-admin">Administrar →</span>
      </a>
    </div>

    <section class="panel-tarjeta">
      <div class="panel-encabezado">
        <h3>Últimos registros</h3>
        <a href="usuarios.php">Ver todos →</a>
      </div>
      <p class="texto-tenue">Movimientos registrados en la plataforma: <?= (int) $totales['movimientos'] ?></p>

      <?php if (mysqli_num_rows($recientes) === 0): ?>
        <p class="texto-tenue">Aún no hay usuarios registrados.</p>
      <?php else: ?>
        <ul class="lista-movimientos">
          <?php while ($u = mysqli_fetch_assoc($recientes)): ?>
            <li>
              <span><?= e($u['nombre'] . ' ' . $u['apellidos']) ?> <span class="texto-tenue">· <?= e($u['correo']) ?></span></span>
              <span class="texto-tenue"><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></span>
              <span class="estado <?= $u['activo'] ? 'estado-activo' : 'estado-inactivo' ?>"><?= $u['activo'] ? 'Activa' : 'Inactiva' ?></span>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>

<?php require '../includes/footer.php'; ?>
