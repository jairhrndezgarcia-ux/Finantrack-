<?php
// dashboard.php - Módulo 5: Panel de métricas del usuario registrado
require_once 'includes/auth.php';
requerir_login();

// El administrador tiene su propio panel
if (es_admin()) {
    redirigir('admin/index.php');
}

require_once 'config/conexion.php';
require_once 'includes/transacciones_crud.php';
require_once 'includes/presupuestos_crud.php';
require_once 'includes/metas_crud.php';
require_once 'includes/metricas.php';
require_once 'includes/componentes.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$periodo   = leer_mes([]); // mes actual
$simbolo   = divisa_usuario($conexion, $usuarioId)['simbolo'];

// Tarjetas: ingresos, gastos y balance del mes + saldo disponible
$totalesMes = totales_periodo($conexion, $usuarioId, [
    'desde' => $periodo['desde'], 'hasta' => $periodo['hasta'], 'tipo' => '', 'categoria' => 0,
]);
$saldo = saldo_disponible($conexion, $usuarioId);

// Presupuestos del mes (los 5 más cercanos a su límite) y metas pendientes
$presupuestos = obtener_presupuestos($conexion, $usuarioId, $periodo);
$resumenPres  = resumen_presupuestos($presupuestos);
$metas        = array_slice(array_filter(obtener_metas($conexion, $usuarioId), fn ($m) => !$m['completada']), 0, 3);

// Datos para las gráficas (se dibujan con JavaScript)
$datosGraficas = [
    'simbolo'    => $simbolo,
    'categorias' => array_map(fn ($c) => [
        'nombre' => $c['icono'] . ' ' . $c['nombre'],
        'color'  => $c['color'],
        'total'  => (float) $c['total'],
    ], gastos_por_categoria($conexion, $usuarioId, $periodo['desde'], $periodo['hasta'])),
    'historial'  => historial_mensual($conexion, $usuarioId, 6),
];

$movimientos = ultimos_movimientos($conexion, $usuarioId, 5);

$titulo = 'Mi panel';
require 'includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Hola, <?= e($_SESSION['usuario_nombre']) ?> 👋</h2>
        <p>Resumen de <?= e($periodo['etiqueta']) ?>.</p>
      </div>
      <div class="botones-encabezado">
        <a href="transacciones.php" class="btn-primario btn-ingreso">+ Movimiento</a>
      </div>
    </div>

    <!-- Tarjetas informativas -->
    <div class="tarjetas-resumen">
      <article class="tarjeta-resumen resumen-ingreso">
        <span>Ingresos del mes</span>
        <strong><?= dinero($totalesMes['ingresos'], $simbolo) ?></strong>
      </article>
      <article class="tarjeta-resumen resumen-gasto">
        <span>Gastos del mes</span>
        <strong><?= dinero($totalesMes['gastos'], $simbolo) ?></strong>
      </article>
      <article class="tarjeta-resumen <?= $totalesMes['balance'] < 0 ? 'resumen-negativo' : 'resumen-balance' ?>">
        <span>Balance del mes</span>
        <strong><?= dinero($totalesMes['balance'], $simbolo) ?></strong>
      </article>
      <article class="tarjeta-resumen <?= $saldo < 0 ? 'resumen-negativo' : 'resumen-saldo' ?>">
        <span>Balance disponible</span>
        <strong><?= dinero($saldo, $simbolo) ?></strong>
      </article>
    </div>

    <?php if ($resumenPres['excedidos'] || $resumenPres['alertas']): ?>
      <p class="aviso-presupuesto">
        ⚠️ Tienes <?= $resumenPres['excedidos'] + $resumenPres['alertas'] ?> presupuesto(s) en alerta este mes.
        <a href="presupuestos.php">Revisar</a>
      </p>
    <?php endif; ?>

    <!-- Gráficas -->
    <div class="grid-panel">
      <section class="panel-tarjeta">
        <h3>Gastos por categoría</h3>
        <div id="grafica-dona" class="grafica-dona-contenedor">
          <p class="texto-tenue">Aún no hay gastos este mes.</p>
        </div>
      </section>

      <section class="panel-tarjeta">
        <h3>Ingresos vs gastos (6 meses)</h3>
        <div id="grafica-barras" class="grafica-barras"></div>
        <div class="leyenda-barras">
          <span><i class="punto punto-ingreso"></i> Ingresos</span>
          <span><i class="punto punto-gasto"></i> Gastos</span>
        </div>
      </section>
    </div>

    <div class="grid-panel">
      <!-- Presupuestos del mes -->
      <section class="panel-tarjeta">
        <div class="panel-encabezado">
          <h3>Presupuestos</h3>
          <a href="presupuestos.php">Ver todos</a>
        </div>
        <?php if ($presupuestos): ?>
          <?php foreach (array_slice($presupuestos, 0, 5) as $p): ?>
            <div class="fila-progreso">
              <div class="fila-progreso-texto">
                <span><?= e($p['icono'] . ' ' . $p['nombre']) ?></span>
                <span class="texto-tenue"><?= dinero($p['gastado'], $simbolo) ?> / <?= dinero($p['monto_limite'], $simbolo) ?></span>
              </div>
              <?= barra_progreso((float) $p['gastado'], (float) $p['monto_limite']) ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="texto-tenue">No tienes presupuestos este mes. <a href="presupuestos.php">Crear uno</a></p>
        <?php endif; ?>
      </section>

      <!-- Metas de ahorro -->
      <section class="panel-tarjeta">
        <div class="panel-encabezado">
          <h3>Metas de ahorro</h3>
          <a href="metas.php">Ver todas</a>
        </div>
        <?php if ($metas): ?>
          <?php foreach ($metas as $m): ?>
            <div class="fila-progreso">
              <div class="fila-progreso-texto">
                <span>💡 <?= e($m['nombre']) ?></span>
                <span class="texto-tenue"><?= dinero($m['monto_actual'], $simbolo) ?> / <?= dinero($m['monto_objetivo'], $simbolo) ?></span>
              </div>
              <?= barra_progreso((float) $m['monto_actual'], (float) $m['monto_objetivo'], 'meta') ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="texto-tenue">No tienes metas pendientes. <a href="metas.php">Crear una</a></p>
        <?php endif; ?>
      </section>
    </div>

    <!-- Últimos movimientos -->
    <section class="panel-tarjeta">
      <div class="panel-encabezado">
        <h3>Últimos movimientos</h3>
        <a href="transacciones.php">Ver todos</a>
      </div>
      <?php if ($movimientos): ?>
        <ul class="lista-movimientos">
          <?php foreach ($movimientos as $mov): ?>
            <li>
              <span><?= e($mov['icono'] . ' ' . $mov['categoria']) ?></span>
              <span class="texto-tenue"><?= e(date('d/m/Y', strtotime($mov['fecha']))) ?></span>
              <strong class="monto-<?= e($mov['tipo']) ?>">
                <?= $mov['tipo'] === 'ingreso' ? '+' : '−' ?><?= dinero($mov['monto'], $simbolo) ?>
              </strong>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="texto-tenue">Todavía no registras movimientos. <a href="transacciones.php">Registrar el primero</a></p>
      <?php endif; ?>
    </section>
  </main>

  <!-- Datos para las gráficas (JavaScript los lee y dibuja) -->
  <script type="application/json" id="datos-graficas">
    <?= json_encode($datosGraficas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
  </script>

<?php require 'includes/footer.php'; ?>
