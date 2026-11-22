<?php
// includes/metricas.php
// Módulo 5 (parte 3): datos para el panel de métricas (dashboard).

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/presupuestos_crud.php'; // MESES

/** Gastos del periodo agrupados por categoría (para la gráfica de dona). */
function gastos_por_categoria(mysqli $cx, int $usuarioId, string $desde, string $hasta): array
{
    return consultar_filas(
        $cx,
        "SELECT c.nombre, c.icono, c.color, SUM(t.monto) AS total
           FROM transacciones t
           JOIN categorias c ON c.id = t.categoria_id
          WHERE t.usuario_id = ? AND t.activo = 1 AND t.tipo = 'gasto' AND t.fecha BETWEEN ? AND ?
          GROUP BY c.id, c.nombre, c.icono, c.color
          ORDER BY total DESC",
        'iss',
        [$usuarioId, $desde, $hasta]
    );
}

/** Ingresos y gastos de los últimos N meses (incluye meses sin movimientos). */
function historial_mensual(mysqli $cx, int $usuarioId, int $meses = 6): array
{
    $inicio = date('Y-m-01', strtotime('-' . ($meses - 1) . ' months', strtotime(date('Y-m-01'))));

    $filas = consultar_filas(
        $cx,
        "SELECT DATE_FORMAT(fecha, '%Y-%m') AS clave,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS ingresos,
                SUM(CASE WHEN tipo = 'gasto'   THEN monto ELSE 0 END) AS gastos
           FROM transacciones
          WHERE usuario_id = ? AND activo = 1 AND fecha >= ?
          GROUP BY clave",
        'is',
        [$usuarioId, $inicio]
    );
    $porMes = array_column($filas, null, 'clave');

    // Rellenar los meses vacíos con 0 para que la gráfica no tenga huecos
    $historial = [];
    for ($i = 0; $i < $meses; $i++) {
        $fecha = strtotime("+$i months", strtotime($inicio));
        $clave = date('Y-m', $fecha);
        $historial[] = [
            'etiqueta' => mb_substr(MESES[(int) date('n', $fecha)], 0, 3),
            'ingresos' => (float) ($porMes[$clave]['ingresos'] ?? 0),
            'gastos'   => (float) ($porMes[$clave]['gastos'] ?? 0),
        ];
    }
    return $historial;
}

/** Últimos movimientos registrados. */
function ultimos_movimientos(mysqli $cx, int $usuarioId, int $limite = 5): array
{
    return consultar_filas(
        $cx,
        'SELECT t.tipo, t.monto, t.fecha, c.nombre AS categoria, c.icono
           FROM transacciones t
           JOIN categorias c ON c.id = t.categoria_id
          WHERE t.usuario_id = ? AND t.activo = 1
          ORDER BY t.fecha DESC, t.id DESC
          LIMIT ?',
        'ii',
        [$usuarioId, $limite]
    );
}
