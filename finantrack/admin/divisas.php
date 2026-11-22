<?php
// admin/divisas.php - Catálogo de divisas (solo administrador)
$base = '../';
require_once '../includes/auth.php';
requerir_admin($base);
require_once '../config/conexion.php';
require_once '../includes/admin_catalogos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_catalogo($conexion, config_divisas(), 'divisas.php');
}

$divisas = obtener_divisas($conexion);

$titulo = 'Divisas';
require '../includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Divisas</h2>
        <p>Monedas que los usuarios pueden elegir como divisa base al registrarse.</p>
      </div>
      <button type="button" class="btn-primario btn-abrir-modal"
              data-modal="modal-divisa" data-accion="crear" data-titulo="Nueva divisa"
              data-valores='{"id":0,"codigo":"","nombre":"","simbolo":""}'>+ Nueva divisa</button>
    </div>

    <div class="tabla-contenedor">
      <table class="tabla tabla-catalogo">
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Símbolo</th>
            <th>Usuarios</th>
            <th>Estado</th>
            <th class="col-acciones">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($divisas as $d): ?>
            <tr class="<?= $d['activo'] ? '' : 'fila-inactiva' ?>">
              <td><strong><?= e($d['codigo']) ?></strong></td>
              <td><?= e($d['nombre']) ?></td>
              <td><?= e($d['simbolo']) ?></td>
              <td><?= (int) $d['usos'] ?></td>
              <td>
                <span class="estado <?= $d['activo'] ? 'estado-activo' : 'estado-inactivo' ?>">
                  <?= $d['activo'] ? 'Activa' : 'Inactiva' ?>
                </span>
              </td>
              <td class="col-acciones">
                <button type="button" class="btn-icono btn-abrir-modal" title="Editar"
                        data-modal="modal-divisa" data-accion="editar" data-titulo="Editar divisa"
                        data-valores="<?= e(json_encode([
                            'id' => (int) $d['id'], 'codigo' => $d['codigo'],
                            'nombre' => $d['nombre'], 'simbolo' => $d['simbolo'],
                        ], JSON_UNESCAPED_UNICODE)) ?>">✏️</button>

                <form method="POST" action="divisas.php">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                  <input type="hidden" name="activo" value="<?= $d['activo'] ? 0 : 1 ?>">
                  <button type="submit" class="btn-icono" title="<?= $d['activo'] ? 'Desactivar' : 'Activar' ?>">
                    <?= $d['activo'] ? '⏸️' : '▶️' ?>
                  </button>
                </form>

                <form method="POST" action="divisas.php" class="form-confirmar"
                      data-confirmar="¿Eliminar la divisa <?= e($d['codigo']) ?>?">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                  <button type="submit" class="btn-icono btn-peligro" title="Eliminar"
                          <?= $d['usos'] > 0 ? 'disabled' : '' ?>>🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="nota-tabla">Una divisa que usa algún usuario no se puede eliminar, pero sí desactivar: deja de ofrecerse en el registro y los usuarios actuales la conservan.</p>
  </main>

  <dialog id="modal-divisa" class="modal">
    <form method="POST" action="divisas.php" class="form-modal form-catalogo">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" value="crear">
      <input type="hidden" name="id" value="0">
      <h3 class="titulo-modal">Nueva divisa</h3>

      <div class="campo-fila">
        <div class="campo-grupo">
          <label for="divisa-codigo">Código (ISO):</label>
          <input type="text" id="divisa-codigo" name="codigo" maxlength="3" required
                 pattern="[A-Za-z]{3}" placeholder="Ej. GBP" class="campo-mayusculas">
        </div>
        <div class="campo-grupo">
          <label for="divisa-simbolo">Símbolo:</label>
          <input type="text" id="divisa-simbolo" name="simbolo" maxlength="5" required placeholder="Ej. £">
        </div>
      </div>

      <div class="campo-grupo">
        <label for="divisa-nombre">Nombre:</label>
        <input type="text" id="divisa-nombre" name="nombre" maxlength="50" required placeholder="Ej. Libra esterlina">
      </div>

      <p class="texto-tenue">Así se verá un monto: <strong id="divisa-ejemplo">$1,234.50</strong></p>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </dialog>

<?php require '../includes/footer.php'; ?>
