<?php
// admin/usuarios.php - Listado de usuarios y estado de sus cuentas (solo administrador)
$base = '../';
require_once '../includes/auth.php';
requerir_admin($base);
require_once '../config/conexion.php';
require_once '../includes/admin_catalogos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_usuarios($conexion, 'usuarios.php');
}

$usuarios = obtener_usuarios($conexion);
$activos  = count(array_filter($usuarios, fn ($u) => (int) $u['activo'] === 1));

$titulo = 'Usuarios';
require '../includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Usuarios registrados</h2>
        <p><?= count($usuarios) ?> cuenta(s): <?= $activos ?> activa(s) y <?= count($usuarios) - $activos ?> inactiva(s).</p>
      </div>
    </div>

    <!-- Filtros del lado del cliente -->
    <div class="barra-filtros">
      <div class="filtros-tipo" role="group" aria-label="Filtrar por estado">
        <button type="button" class="filtro activo" data-filtro-estado="todos">Todos</button>
        <button type="button" class="filtro" data-filtro-estado="1">Activos</button>
        <button type="button" class="filtro" data-filtro-estado="0">Inactivos</button>
      </div>
      <input type="search" id="buscar-usuario" class="campo-buscar" placeholder="Buscar por nombre o correo...">
    </div>

    <div class="tabla-contenedor">
      <table class="tabla" id="tabla-usuarios">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Divisa</th>
            <th>Movimientos</th>
            <th>Último acceso</th>
            <th>Registro</th>
            <th>Estado</th>
            <th class="col-acciones">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr data-estado="<?= (int) $u['activo'] ?>"
                data-busqueda="<?= e(mb_strtolower($u['nombre'] . ' ' . $u['apellidos'] . ' ' . $u['correo'])) ?>"
                class="<?= $u['activo'] ? '' : 'fila-inactiva' ?>">
              <td><?= e($u['nombre'] . ' ' . $u['apellidos']) ?></td>
              <td><?= e($u['correo']) ?></td>
              <td><?= e($u['divisa']) ?></td>
              <td><?= (int) $u['movimientos'] ?></td>
              <td><?= $u['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_acceso']))) : 'Nunca' ?></td>
              <td><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
              <td>
                <span class="estado <?= $u['activo'] ? 'estado-activo' : 'estado-inactivo' ?>">
                  <?= $u['activo'] ? 'Activa' : 'Inactiva' ?>
                </span>
              </td>
              <td class="col-acciones">
                <form method="POST" action="usuarios.php" class="form-confirmar"
                      data-confirmar="<?= $u['activo']
                          ? '¿Desactivar la cuenta de ' . e($u['nombre']) . '? Ya no podrá iniciar sesión.'
                          : '¿Reactivar la cuenta de ' . e($u['nombre']) . '?' ?>">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="activo" value="<?= $u['activo'] ? 0 : 1 ?>">
                  <button type="submit" class="<?= $u['activo'] ? 'btn-secundario' : 'btn-primario' ?> btn-chico">
                    <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr class="<?= $usuarios ? 'oculto' : '' ?>" id="fila-sin-usuarios">
            <td colspan="8" class="tabla-vacia">No hay usuarios que coincidan.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>

<?php require '../includes/footer.php'; ?>
