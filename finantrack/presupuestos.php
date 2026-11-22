<?php
// presupuestos.php - Módulo 5: presupuestos mensuales por categoría
require_once 'includes/auth.php';
requerir_login();

if (es_admin()) {
    redirigir('admin/index.php');
}

require_once 'config/conexion.php';
require_once 'includes/presupuestos_crud.php';
require_once 'includes/transacciones_crud.php'; // divisa_usuario()
require_once 'includes/componentes.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$periodo   = leer_mes($_GET);
$urlActual = 'presupuestos.php?mes=' . $periodo['clave'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_presupuestos($conexion, $usuarioId, $periodo, $urlActual);
}

$presupuestos  = obtener_presupuestos($conexion, $usuarioId, $periodo);
$resumen       = resumen_presupuestos($presupuestos);
$disponibles   = categorias_sin_presupuesto($conexion, $usuarioId, $periodo);
$simbolo       = divisa_usuario($conexion, $usuarioId)['simbolo'];

$titulo = 'Presupuestos';
require 'includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Presupuestos mensuales</h2>
        <p>Fija un tope de gasto por categoría y compáralo con lo que realmente gastas.</p>
      </div>
      <button type="button" class="btn-primario" id="btn-nuevo-presupuesto"
              <?= $disponibles ? '' : 'disabled title="Todas tus categorías de gasto ya tienen presupuesto"' ?>>
        + Nuevo presupuesto
      </button>
    </div>

    <!-- Navegación entre meses -->
    <div class="navegador-mes">
      <a href="presupuestos.php?mes=<?= e($periodo['anterior']) ?>" class="btn-icono" title="Mes anterior">◀</a>
      <h3><?= e($periodo['etiqueta']) ?></h3>
      <a href="presupuestos.php?mes=<?= e($periodo['siguiente']) ?>" class="btn-icono" title="Mes siguiente">▶</a>
      <?php if (!$periodo['es_actual']): ?>
        <a href="presupuestos.php" class="enlace-hoy">Ir al mes actual</a>
      <?php endif; ?>
    </div>

    <?php if ($presupuestos): ?>
      <!-- Comparador general: presupuestado vs gastado -->
      <div class="tarjetas-resumen">
        <article class="tarjeta-resumen resumen-balance">
          <span>Total presupuestado</span>
          <strong><?= dinero($resumen['limite'], $simbolo) ?></strong>
        </article>
        <article class="tarjeta-resumen resumen-gasto">
          <span>Gastado en esas categorías</span>
          <strong><?= dinero($resumen['gastado'], $simbolo) ?></strong>
        </article>
        <article class="tarjeta-resumen <?= $resumen['disponible'] < 0 ? 'resumen-negativo' : 'resumen-ingreso' ?>">
          <span><?= $resumen['disponible'] < 0 ? 'Excedido por' : 'Disponible para gastar' ?></span>
          <strong><?= dinero(abs($resumen['disponible']), $simbolo) ?></strong>
        </article>
      </div>

      <?php if ($resumen['excedidos'] || $resumen['alertas']): ?>
        <p class="aviso-presupuesto">
          ⚠️
          <?php if ($resumen['excedidos']): ?><strong><?= $resumen['excedidos'] ?></strong> categoría(s) superaron su límite. <?php endif; ?>
          <?php if ($resumen['alertas']): ?><strong><?= $resumen['alertas'] ?></strong> categoría(s) ya pasaron el 80 %.<?php endif; ?>
        </p>
      <?php endif; ?>

      <div class="lista-presupuestos">
        <?php foreach ($presupuestos as $p): ?>
          <article class="tarjeta-presupuesto" style="--color-cat: <?= e($p['color']) ?>">
            <div class="presupuesto-encabezado">
              <div class="presupuesto-titulo">
                <span class="cat-icono cat-icono-chico"><?= e($p['icono']) ?></span>
                <h4><?= e($p['nombre']) ?></h4>
              </div>
              <div class="cat-acciones">
                <button type="button" class="btn-icono btn-editar-presupuesto" title="Editar límite"
                        data-id="<?= (int) $p['id'] ?>"
                        data-nombre="<?= e($p['icono'] . ' ' . $p['nombre']) ?>"
                        data-limite="<?= e($p['monto_limite']) ?>"
                        data-gastado="<?= e($p['gastado']) ?>">✏️</button>
                <form method="POST" action="<?= e($urlActual) ?>" class="form-confirmar"
                      data-confirmar="¿Eliminar el presupuesto de «<?= e($p['nombre']) ?>»?">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" class="btn-icono btn-peligro" title="Eliminar">🗑️</button>
                </form>
              </div>
            </div>

            <?= barra_progreso((float) $p['gastado'], (float) $p['monto_limite']) ?>

            <p class="presupuesto-montos">
              <span><?= dinero($p['gastado'], $simbolo) ?> de <?= dinero($p['monto_limite'], $simbolo) ?></span>
              <?php $resta = (float) $p['monto_limite'] - (float) $p['gastado']; ?>
              <span class="<?= $resta < 0 ? 'texto-alerta' : 'texto-tenue' ?>">
                <?= $resta < 0 ? 'Excedido ' . dinero(-$resta, $simbolo) : 'Quedan ' . dinero($resta, $simbolo) ?>
              </span>
            </p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="estado-vacio">
        <div class="icono-tarjeta">🎯</div>
        <p>No tienes presupuestos para <?= e($periodo['etiqueta']) ?>.</p>
        <p class="texto-tenue">Crea uno o copia los del mes anterior.</p>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= e($urlActual) ?>" class="copiar-presupuestos">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" value="copiar">
      <button type="submit" class="btn-secundario">📋 Copiar presupuestos del mes anterior</button>
    </form>
  </main>

  <!-- Modal de alta / modificación -->
  <dialog id="modal-presupuesto" class="modal">
    <form method="POST" action="<?= e($urlActual) ?>" id="form-presupuesto" class="form-modal">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" id="pres-accion" value="crear">
      <input type="hidden" name="id" id="pres-id" value="0">

      <h3 id="modal-pres-titulo">Nuevo presupuesto</h3>
      <p class="texto-tenue">Periodo: <?= e($periodo['etiqueta']) ?></p>

      <div class="campo-grupo" id="grupo-pres-categoria">
        <label for="pres-categoria">Categoría de gasto:</label>
        <select id="pres-categoria" name="categoria_id">
          <?php foreach ($disponibles as $c): ?>
            <option value="<?= (int) $c['id'] ?>" data-gastado="<?= e($c['gastado']) ?>">
              <?= e($c['icono'] . ' ' . $c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <p class="campo-fijo oculto" id="pres-nombre-fijo"></p>

      <div class="campo-grupo">
        <label for="pres-limite">Límite mensual (<?= e($simbolo) ?>):</label>
        <input type="number" id="pres-limite" name="monto_limite" min="0.01" step="0.01" required placeholder="0.00">
      </div>

      <!-- Vista previa reactiva: cómo quedaría con lo ya gastado este mes -->
      <div class="vista-previa-presupuesto">
        <p class="texto-tenue">Gastado este mes: <strong id="pres-gastado-texto"><?= e($simbolo) ?>0.00</strong></p>
        <div class="progreso" id="pres-previa" data-actual="0" data-limite="0" data-modo="presupuesto">
          <div class="barra-progreso"><div class="barra-relleno"></div></div>
          <span class="progreso-porcentaje">0%</span>
        </div>
      </div>

      <span class="texto-error" id="error-presupuesto"></span>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </dialog>

  <script>window.SIMBOLO_DIVISA = <?= json_encode($simbolo, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;</script>

<?php require 'includes/footer.php'; ?>
