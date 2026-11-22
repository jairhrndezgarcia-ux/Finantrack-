<?php
// transacciones.php - Módulo 4: Gestor de Transacciones Financieras (usuario registrado)
require_once 'includes/auth.php';
requerir_login();

if (es_admin()) {
    redirigir('admin/index.php');
}

require_once 'config/conexion.php';
require_once 'includes/transacciones_crud.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$filtros   = leer_filtros($_GET);

// URL actual con sus filtros: después de guardar se regresa a la misma vista
$urlActual = 'transacciones.php?' . http_build_query($filtros);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_transacciones($conexion, $usuarioId, $urlActual);
}

// Lectura
$transacciones = obtener_transacciones($conexion, $usuarioId, $filtros);
$totales       = totales_periodo($conexion, $usuarioId, $filtros);
$saldo         = saldo_disponible($conexion, $usuarioId);
$categorias    = categorias_disponibles($conexion, $usuarioId);
$metodos       = metodos_pago_activos($conexion);
$divisa        = divisa_usuario($conexion, $usuarioId);
$simbolo       = $divisa['simbolo'];
$hoy           = date('Y-m-d');

$titulo = 'Transacciones';
require 'includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Transacciones</h2>
        <p>Registra y consulta tus ingresos y gastos (<?= e($divisa['codigo']) ?>).</p>
      </div>
      <div class="botones-encabezado">
        <button type="button" class="btn-primario btn-ingreso" data-nuevo="ingreso">+ Ingreso</button>
        <button type="button" class="btn-primario btn-gasto" data-nuevo="gasto">+ Gasto</button>
      </div>
    </div>

    <!-- Tarjetas de resumen del periodo -->
    <div class="tarjetas-resumen">
      <article class="tarjeta-resumen resumen-ingreso">
        <span>Ingresos del periodo</span>
        <strong><?= dinero($totales['ingresos'], $simbolo) ?></strong>
      </article>
      <article class="tarjeta-resumen resumen-gasto">
        <span>Gastos del periodo</span>
        <strong><?= dinero($totales['gastos'], $simbolo) ?></strong>
      </article>
      <article class="tarjeta-resumen <?= $totales['balance'] < 0 ? 'resumen-negativo' : 'resumen-balance' ?>">
        <span>Balance del periodo</span>
        <strong><?= dinero($totales['balance'], $simbolo) ?></strong>
      </article>
      <article class="tarjeta-resumen <?= $saldo < 0 ? 'resumen-negativo' : 'resumen-saldo' ?>">
        <span>Saldo disponible total</span>
        <strong><?= dinero($saldo, $simbolo) ?></strong>
      </article>
    </div>

    <!-- Filtros: rango de fechas, tipo y categoría (se envían por GET) -->
    <form method="GET" action="transacciones.php" class="form-filtros" id="form-filtros">
      <div class="rangos-rapidos" role="group" aria-label="Rangos rápidos">
        <button type="button" class="filtro" data-rango="mes">Este mes</button>
        <button type="button" class="filtro" data-rango="mes-pasado">Mes pasado</button>
        <button type="button" class="filtro" data-rango="30">Últimos 30 días</button>
        <button type="button" class="filtro" data-rango="anio">Este año</button>
      </div>

      <div class="campos-filtro">
        <div class="campo-grupo">
          <label for="f-desde">Desde:</label>
          <input type="date" id="f-desde" name="desde" value="<?= e($filtros['desde']) ?>">
        </div>
        <div class="campo-grupo">
          <label for="f-hasta">Hasta:</label>
          <input type="date" id="f-hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
        </div>
        <div class="campo-grupo">
          <label for="f-tipo">Tipo:</label>
          <select id="f-tipo" name="tipo">
            <option value="">Todos</option>
            <option value="ingreso" <?= $filtros['tipo'] === 'ingreso' ? 'selected' : '' ?>>Ingresos</option>
            <option value="gasto" <?= $filtros['tipo'] === 'gasto' ? 'selected' : '' ?>>Gastos</option>
          </select>
        </div>
        <div class="campo-grupo">
          <label for="f-categoria">Categoría:</label>
          <select id="f-categoria" name="categoria">
            <option value="0">Todas</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= $filtros['categoria'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['icono'] . ' ' . $c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="acciones-filtro">
          <button type="submit" class="btn-primario">Filtrar</button>
          <a href="transacciones.php" class="btn-secundario">Limpiar</a>
        </div>
      </div>
    </form>

    <!-- Tabla de movimientos -->
    <p class="contador-resultados">
      <?= count($transacciones) ?> movimiento(s) del
      <?= e(date('d/m/Y', strtotime($filtros['desde']))) ?> al <?= e(date('d/m/Y', strtotime($filtros['hasta']))) ?>
    </p>

    <div class="tabla-contenedor">
      <table class="tabla tabla-transacciones">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Categoría</th>
            <th>Notas</th>
            <th>Método</th>
            <th class="col-monto">Monto</th>
            <th class="col-acciones">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$transacciones): ?>
            <tr><td colspan="6" class="tabla-vacia">No hay movimientos en este periodo. ¡Registra el primero!</td></tr>
          <?php endif; ?>

          <?php foreach ($transacciones as $t): ?>
            <tr>
              <td><?= e(date('d/m/Y', strtotime($t['fecha']))) ?></td>
              <td>
                <span class="chip-categoria" style="--color-cat: <?= e($t['color']) ?>">
                  <?= e($t['icono']) ?> <?= e($t['categoria']) ?>
                </span>
              </td>
              <td class="col-notas"><?= $t['notas'] !== null ? e($t['notas']) : '<span class="texto-tenue">—</span>' ?></td>
              <td><?= e($t['metodo']) ?></td>
              <td class="col-monto monto-<?= e($t['tipo']) ?>">
                <?= $t['tipo'] === 'ingreso' ? '+' : '−' ?><?= dinero($t['monto'], $simbolo) ?>
              </td>
              <td class="col-acciones">
                <button type="button" class="btn-icono btn-editar-trans" title="Editar"
                        data-id="<?= (int) $t['id'] ?>"
                        data-tipo="<?= e($t['tipo']) ?>"
                        data-monto="<?= e($t['monto']) ?>"
                        data-fecha="<?= e($t['fecha']) ?>"
                        data-categoria="<?= (int) $t['categoria_id'] ?>"
                        data-metodo="<?= (int) $t['metodo_pago_id'] ?>"
                        data-notas="<?= e($t['notas']) ?>">✏️</button>

                <form method="POST" action="<?= e($urlActual) ?>" class="form-eliminar-trans"
                      data-descripcion="<?= e($t['categoria'] . ' por ' . dinero($t['monto'], $simbolo)) ?>">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <button type="submit" class="btn-icono btn-peligro" title="Eliminar">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Modal de alta / modificación de movimientos -->
  <dialog id="modal-transaccion" class="modal">
    <form method="POST" action="<?= e($urlActual) ?>" id="form-transaccion" class="form-modal">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" id="trans-accion" value="crear">
      <input type="hidden" name="id" id="trans-id" value="0">

      <h3 id="modal-trans-titulo">Nuevo movimiento</h3>

      <!-- Selector de tipo (ingreso / gasto) -->
      <div class="selector-tipo" role="radiogroup" aria-label="Tipo de movimiento">
        <label>
          <input type="radio" name="tipo" value="gasto" checked>
          <span>Gasto</span>
        </label>
        <label>
          <input type="radio" name="tipo" value="ingreso">
          <span>Ingreso</span>
        </label>
      </div>

      <div class="campo-fila">
        <div class="campo-grupo">
          <label for="trans-monto">Monto (<?= e($simbolo) ?>):</label>
          <input type="number" id="trans-monto" name="monto" min="0.01" max="9999999999.99" step="0.01"
                 placeholder="0.00" required>
        </div>
        <div class="campo-grupo">
          <label for="trans-fecha">Fecha:</label>
          <input type="date" id="trans-fecha" name="fecha" max="<?= $hoy ?>" value="<?= $hoy ?>" required>
        </div>
      </div>

      <div class="campo-fila">
        <div class="campo-grupo">
          <label for="trans-categoria">Categoría:</label>
          <select id="trans-categoria" name="categoria_id" required>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= (int) $c['id'] ?>" data-tipo="<?= e($c['tipo']) ?>">
                <?= e($c['icono'] . ' ' . $c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo-grupo">
          <label for="trans-metodo">Método de pago:</label>
          <select id="trans-metodo" name="metodo_pago_id" required>
            <?php foreach ($metodos as $m): ?>
              <option value="<?= (int) $m['id'] ?>"><?= e($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="campo-grupo">
        <label for="trans-notas">Notas (opcional):</label>
        <textarea id="trans-notas" name="notas" maxlength="255" rows="2"
                  placeholder="Ej. Despensa de la semana"></textarea>
        <small class="contador-caracteres" id="contador-notas">0 / 255</small>
      </div>

      <span class="texto-error" id="error-transaccion"></span>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" id="btn-cancelar-trans">Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </dialog>

<?php require 'includes/footer.php'; ?>
