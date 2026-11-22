<?php
// admin/index.php - Panel de administración (rol 1)
$base = '../';
require_once '../includes/auth.php';
requerir_admin($base);
require_once '../config/conexion.php';

// Totales de los catálogos
$totales = mysqli_fetch_assoc(mysqli_query($conexion, '
    SELECT
      (SELECT COUNT(*) FROM usuarios WHERE rol_id = 2)                        AS usuarios,
      (SELECT COUNT(*) FROM categorias WHERE usuario_id IS NULL AND activo = 1) AS categorias,
      (SELECT COUNT(*) FROM divisas WHERE activo = 1)                         AS divisas,
      (SELECT COUNT(*) FROM metodos_pago WHERE activo = 1)                    AS metodos
'));

// Listado de usuarios registrados y su estado de actividad
$usuarios = mysqli_query($conexion, '
    SELECT u.nombre, u.apellidos, u.correo, d.codigo AS divisa,
           u.activo, u.ultimo_acceso, u.creado_en
      FROM usuarios u
      JOIN divisas d ON d.id = u.divisa_id
     WHERE u.rol_id = 2
     ORDER BY u.creado_en DESC
');

$titulo = 'Panel de administración';
require '../includes/header.php';
?>

  <main class="seccion-padding">
    <h2 class="titulo-seccion">Panel de administración</h2>
    <p class="subtitulo-seccion">Gestión de catálogos globales y supervisión de cuentas.</p>

    <div class="contenedor-grid">
      <article class="tarjeta-funcion">
        <div class="icono-tarjeta">👥</div>
        <h3><?= (int) $totales['usuarios'] ?> usuarios</h3>
        <p>Cuentas registradas.</p>
      </article>
      <article class="tarjeta-funcion">
        <div class="icono-tarjeta">🏷️</div>
        <h3><?= (int) $totales['categorias'] ?> categorías</h3>
        <p>Categorías globales activas.</p>
        <a href="categorias.php" class="btn-primario btn-chico">Administrar</a>
      </article>
      <article class="tarjeta-funcion">
        <div class="icono-tarjeta">💱</div>
        <h3><?= (int) $totales['divisas'] ?> divisas · <?= (int) $totales['metodos'] ?> métodos</h3>
        <p>Divisas y métodos de pago activos.</p>
        <span class="etiqueta-pronto">Gestión próximamente</span>
      </article>
    </div>

    <h3 class="subtitulo-tabla">Usuarios registrados</h3>
    <div class="tabla-contenedor">
      <table class="tabla">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Divisa</th>
            <th>Estado</th>
            <th>Último acceso</th>
            <th>Registro</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($usuarios) === 0): ?>
            <tr><td colspan="6" class="tabla-vacia">Aún no hay usuarios registrados.</td></tr>
          <?php endif; ?>
          <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
            <tr>
              <td><?= e($u['nombre'] . ' ' . $u['apellidos']) ?></td>
              <td><?= e($u['correo']) ?></td>
              <td><?= e($u['divisa']) ?></td>
              <td>
                <span class="estado <?= $u['activo'] ? 'estado-activo' : 'estado-inactivo' ?>">
                  <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                </span>
              </td>
              <td><?= $u['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_acceso']))) : 'Nunca' ?></td>
              <td><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>

<?php require '../includes/footer.php'; ?>
