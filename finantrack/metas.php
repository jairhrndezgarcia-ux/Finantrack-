<?php
// metas.php - Módulo 5: metas de ahorro con fecha límite
require_once 'includes/auth.php';
requerir_login();

if (es_admin()) {
    redirigir('admin/index.php');
}

require_once 'config/conexion.php';
require_once 'includes/metas_crud.php';
require_once 'includes/transacciones_crud.php'; // divisa_usuario()
require_once 'includes/componentes.php';

$usuarioId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_metas($conexion, $usuarioId, 'metas.php');
}

$metas   = obtener_metas($conexion, $usuarioId);
$simbolo = divisa_usuario($conexion, $usuarioId)['simbolo'];
$hoy     = date('Y-m-d');

$totalObjetivo = array_sum(array_column($metas, 'monto_objetivo'));
$totalAhorrado = array_sum(array_column($metas, 'monto_actual'));
$completadas   = count(array_filter($metas, fn ($m) => $m['completada']));

$titulo = 'Metas de ahorro';
require 'includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Metas de ahorro</h2>
        <p>Define cuánto quieres ahorrar, para cuándo, y registra tus avances.</p>
      </div>
      <button type="button" class="btn-primario" id="btn-nueva-meta">+ Nueva meta</button>
    </div>

    <?php if ($metas): ?>
      <div class="tarjetas-resumen">
        <article class="tarjeta-resumen resumen-ingreso">
          <span>Total ahorrado</span>
          <strong><?= dinero($totalAhorrado, $simbolo) ?></strong>
        </article>
        <article class="tarjeta-resumen resumen-balance">
          <span>Suma de objetivos</span>
          <strong><?= dinero($totalObjetivo, $simbolo) ?></strong>
        </article>
        <article class="tarjeta-resumen">
          <span>Metas completadas</span>
          <strong><?= $completadas ?> de <?= count($metas) ?></strong>
        </article>
      </div>

      <div class="grid-metas">
        <?php foreach ($metas as $m): ?>
          <article class="tarjeta-meta <?= $m['completada'] ? 'meta-completada' : '' ?> <?= $m['vencida'] ? 'meta-vencida' : '' ?>">
            <div class="presupuesto-encabezado">
              <h4><?= $m['completada'] ? '🏆' : '💡' ?> <?= e($m['nombre']) ?></h4>
              <div class="cat-acciones">
                <button type="button" class="btn-icono btn-editar-meta" title="Editar"
                        data-id="<?= (int) $m['id'] ?>"
                        data-nombre="<?= e($m['nombre']) ?>"
                        data-objetivo="<?= e($m['monto_objetivo']) ?>"
                        data-fecha="<?= e($m['fecha_limite']) ?>">✏️</button>
                <form method="POST" action="metas.php" class="form-confirmar"
                      data-confirmar="¿Eliminar la meta «<?= e($m['nombre']) ?>»? Se perderá su historial de ahorro.">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <button type="submit" class="btn-icono btn-peligro" title="Eliminar">🗑️</button>
                </form>
              </div>
            </div>

            <p class="meta-montos">
              <strong><?= dinero($m['monto_actual'], $simbolo) ?></strong>
              <span class="texto-tenue">de <?= dinero($m['monto_objetivo'], $simbolo) ?></span>
            </p>

            <?= barra_progreso((float) $m['monto_actual'], (float) $m['monto_objetivo'], 'meta') ?>

            <ul class="meta-detalles">
              <li>📅 Fecha límite: <?= e(date('d/m/Y', strtotime($m['fecha_limite']))) ?></li>
              <?php if ($m['completada']): ?>
                <li class="texto-exito">✔ ¡Meta alcanzada!</li>
              <?php elseif ($m['vencida']): ?>
                <li class="texto-alerta">⏰ Venció hace <?= abs($m['dias']) ?> día(s). Faltaron <?= dinero($m['faltante'], $simbolo) ?>.</li>
              <?php else: ?>
                <li>⏳ Quedan <?= $m['dias'] ?> día(s) · faltan <?= dinero($m['faltante'], $simbolo) ?></li>
                <li>💰 Ahorro sugerido: <strong><?= dinero($m['ahorro_mensual'], $simbolo) ?></strong> al mes</li>
              <?php endif; ?>
            </ul>

            <div class="meta-botones">
              <button type="button" class="btn-primario btn-chico btn-movimiento-meta"
                      data-accion="abonar" data-id="<?= (int) $m['id'] ?>" data-nombre="<?= e($m['nombre']) ?>"
                      data-actual="<?= e($m['monto_actual']) ?>" data-objetivo="<?= e($m['monto_objetivo']) ?>">+ Abonar</button>
              <?php if ((float) $m['monto_actual'] > 0): ?>
                <button type="button" class="btn-secundario btn-chico btn-movimiento-meta"
                        data-accion="retirar" data-id="<?= (int) $m['id'] ?>" data-nombre="<?= e($m['nombre']) ?>"
                        data-actual="<?= e($m['monto_actual']) ?>" data-objetivo="<?= e($m['monto_objetivo']) ?>">− Retirar</button>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="estado-vacio">
        <div class="icono-tarjeta">💡</div>
        <p>Aún no tienes metas de ahorro.</p>
        <p class="texto-tenue">Ej. "Fondo de emergencia", "Viaje", "Laptop nueva".</p>
      </div>
    <?php endif; ?>
  </main>

  <!-- Modal de alta / modificación de metas -->
  <dialog id="modal-meta" class="modal">
    <form method="POST" action="metas.php" id="form-meta" class="form-modal">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" id="meta-accion" value="crear">
      <input type="hidden" name="id" id="meta-id" value="0">

      <h3 id="modal-meta-titulo">Nueva meta</h3>

      <div class="campo-grupo">
        <label for="meta-nombre">Nombre de la meta:</label>
        <input type="text" id="meta-nombre" name="nombre" maxlength="80" required placeholder="Ej. Fondo de emergencia">
      </div>

      <div class="campo-fila">
        <div class="campo-grupo">
          <label for="meta-objetivo">Objetivo (<?= e($simbolo) ?>):</label>
          <input type="number" id="meta-objetivo" name="monto_objetivo" min="0.01" step="0.01" required placeholder="0.00">
        </div>
        <div class="campo-grupo">
          <label for="meta-fecha">Fecha límite:</label>
          <input type="date" id="meta-fecha" name="fecha_limite" min="<?= $hoy ?>" required>
        </div>
      </div>

      <div class="campo-grupo" id="grupo-meta-inicial">
        <label for="meta-inicial">Ahorro inicial (opcional):</label>
        <input type="number" id="meta-inicial" name="monto_actual" min="0" step="0.01" placeholder="0.00">
      </div>

      <!-- Cálculo en vivo con JavaScript -->
      <p class="calculo-meta" id="calculo-meta"></p>

      <span class="texto-error" id="error-meta"></span>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </dialog>

  <!-- Modal para abonar / retirar -->
  <dialog id="modal-movimiento-meta" class="modal">
    <form method="POST" action="metas.php" id="form-movimiento-meta" class="form-modal">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" id="mov-accion" value="abonar">
      <input type="hidden" name="id" id="mov-id" value="0">

      <h3 id="mov-titulo">Abonar a la meta</h3>
      <p class="texto-tenue" id="mov-nombre"></p>

      <div class="campo-grupo">
        <label for="mov-monto">Monto (<?= e($simbolo) ?>):</label>
        <input type="number" id="mov-monto" name="monto" min="0.01" step="0.01" required placeholder="0.00">
      </div>

      <p class="texto-tenue">Así quedaría tu meta:</p>
      <div class="progreso" id="mov-previa" data-actual="0" data-limite="1" data-modo="meta">
        <div class="barra-progreso"><div class="barra-relleno"></div></div>
        <span class="progreso-porcentaje">0%</span>
      </div>

      <span class="texto-error" id="error-mov"></span>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario" id="mov-guardar">Abonar</button>
      </div>
    </form>
  </dialog>

  <script>window.SIMBOLO_DIVISA = <?= json_encode($simbolo, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;</script>

<?php require 'includes/footer.php'; ?>
