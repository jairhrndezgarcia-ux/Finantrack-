<?php
// admin/metodos.php - Catálogo de métodos de pago (solo administrador)
$base = '../';
require_once '../includes/auth.php';
requerir_admin($base);
require_once '../config/conexion.php';
require_once '../includes/admin_catalogos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_catalogo($conexion, config_metodos(), 'metodos.php');
}

$metodos = obtener_metodos($conexion);

$titulo = 'Métodos de pago';
require '../includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Métodos de pago</h2>
        <p>Opciones que los usuarios eligen al registrar un movimiento (Efectivo, Débito, Crédito...).</p>
      </div>
      <button type="button" class="btn-primario btn-abrir-modal"
              data-modal="modal-metodo" data-accion="crear" data-titulo="Nuevo método de pago"
              data-valores='{"id":0,"nombre":""}'>+ Nuevo método</button>
    </div>

    <div class="tabla-contenedor">
      <table class="tabla tabla-catalogo">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Movimientos</th>
            <th>Estado</th>
            <th class="col-acciones">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($metodos as $m): ?>
            <tr class="<?= $m['activo'] ? '' : 'fila-inactiva' ?>">
              <td><strong><?= e($m['nombre']) ?></strong></td>
              <td><?= (int) $m['usos'] ?></td>
              <td>
                <span class="estado <?= $m['activo'] ? 'estado-activo' : 'estado-inactivo' ?>">
                  <?= $m['activo'] ? 'Activo' : 'Inactivo' ?>
                </span>
              </td>
              <td class="col-acciones">
                <button type="button" class="btn-icono btn-abrir-modal" title="Editar"
                        data-modal="modal-metodo" data-accion="editar" data-titulo="Editar método de pago"
                        data-valores="<?= e(json_encode(['id' => (int) $m['id'], 'nombre' => $m['nombre']])) ?>">✏️</button>

                <form method="POST" action="metodos.php">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <input type="hidden" name="activo" value="<?= $m['activo'] ? 0 : 1 ?>">
                  <button type="submit" class="btn-icono" title="<?= $m['activo'] ? 'Desactivar' : 'Activar' ?>">
                    <?= $m['activo'] ? '⏸️' : '▶️' ?>
                  </button>
                </form>

                <form method="POST" action="metodos.php" class="form-confirmar"
                      data-confirmar="¿Eliminar el método «<?= e($m['nombre']) ?>»?">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <button type="submit" class="btn-icono btn-peligro" title="Eliminar"
                          <?= $m['usos'] > 0 ? 'disabled' : '' ?>>🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="nota-tabla">Un método con movimientos no se puede eliminar, pero sí desactivar: deja de aparecer en los formularios y los movimientos anteriores se conservan.</p>
  </main>

  <dialog id="modal-metodo" class="modal">
    <form method="POST" action="metodos.php" class="form-modal form-catalogo">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" value="crear">
      <input type="hidden" name="id" value="0">
      <h3 class="titulo-modal">Nuevo método de pago</h3>

      <div class="campo-grupo">
        <label for="metodo-nombre">Nombre:</label>
        <input type="text" id="metodo-nombre" name="nombre" maxlength="40" required placeholder="Ej. Vales de despensa">
      </div>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </dialog>

<?php require '../includes/footer.php'; ?>
