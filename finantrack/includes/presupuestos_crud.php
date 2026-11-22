<?php
// includes/presupuestos_crud.php
// Módulo 5 (parte 1): presupuestos mensuales por categoría de gasto.

require_once __DIR__ . '/db.php';

const MESES = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
               'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

/**
 * Lee el mes de la URL (?mes=AAAA-MM). Si falta o es inválido usa el mes actual.
 * Devuelve año, mes, primer y último día, etiqueta y meses vecinos.
 */
function leer_mes(array $get): array
{
    $texto = (string) ($get['mes'] ?? '');
    if (preg_match('/^(\d{4})-(\d{2})$/', $texto, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12
        && (int) $m[1] >= 2000 && (int) $m[1] <= 2100) {
        $anio = (int) $m[1];
        $mes  = (int) $m[2];
    } else {
        $anio = (int) date('Y');
        $mes  = (int) date('n');
    }

    $primerDia = sprintf('%04d-%02d-01', $anio, $mes);
    $anterior  = date('Y-m', strtotime("$primerDia -1 month"));
    $siguiente = date('Y-m', strtotime("$primerDia +1 month"));

    return [
        'anio'      => $anio,
        'mes'       => $mes,
        'clave'     => sprintf('%04d-%02d', $anio, $mes),
        'desde'     => $primerDia,
        'hasta'     => date('Y-m-t', strtotime($primerDia)),
        'etiqueta'  => MESES[$mes] . ' ' . $anio,
        'anterior'  => $anterior,
        'siguiente' => $siguiente,
        'es_actual' => $anio === (int) date('Y') && $mes === (int) date('n'),
    ];
}

/** Presupuestos del mes con lo gastado realmente en cada categoría. */
function obtener_presupuestos(mysqli $cx, int $usuarioId, array $periodo): array
{
    $filas = consultar_filas(
        $cx,
        "SELECT p.id, p.categoria_id, p.monto_limite, c.nombre, c.icono, c.color,
                COALESCE((SELECT SUM(t.monto) FROM transacciones t
                           WHERE t.usuario_id = p.usuario_id AND t.categoria_id = p.categoria_id
                             AND t.tipo = 'gasto' AND t.activo = 1
                             AND t.fecha BETWEEN ? AND ?), 0) AS gastado
           FROM presupuestos p
           JOIN categorias c ON c.id = p.categoria_id
          WHERE p.usuario_id = ? AND p.anio = ? AND p.mes = ?",
        'ssiii',
        [$periodo['desde'], $periodo['hasta'], $usuarioId, $periodo['anio'], $periodo['mes']]
    );

    foreach ($filas as &$f) {
        $f['porcentaje'] = (float) $f['gastado'] / (float) $f['monto_limite'] * 100;
    }
    unset($f);

    // Los más cercanos a su límite primero
    usort($filas, fn ($a, $b) => $b['porcentaje'] <=> $a['porcentaje']);
    return $filas;
}

/** Totales del mes: presupuestado, gastado en esas categorías y cuántas alertas hay. */
function resumen_presupuestos(array $presupuestos): array
{
    $resumen = ['limite' => 0.0, 'gastado' => 0.0, 'alertas' => 0, 'excedidos' => 0];
    foreach ($presupuestos as $p) {
        $resumen['limite']  += (float) $p['monto_limite'];
        $resumen['gastado'] += (float) $p['gastado'];
        if ($p['porcentaje'] >= 100) {
            $resumen['excedidos']++;
        } elseif ($p['porcentaje'] >= 80) {
            $resumen['alertas']++;
        }
    }
    $resumen['disponible'] = $resumen['limite'] - $resumen['gastado'];
    return $resumen;
}

/** Categorías de gasto que todavía no tienen presupuesto en el mes. */
function categorias_sin_presupuesto(mysqli $cx, int $usuarioId, array $periodo): array
{
    return consultar_filas(
        $cx,
        "SELECT c.id, c.nombre, c.icono,
                COALESCE((SELECT SUM(t.monto) FROM transacciones t
                           WHERE t.usuario_id = ? AND t.categoria_id = c.id AND t.tipo = 'gasto'
                             AND t.activo = 1 AND t.fecha BETWEEN ? AND ?), 0) AS gastado
           FROM categorias c
          WHERE c.activo = 1 AND c.tipo = 'gasto' AND (c.usuario_id IS NULL OR c.usuario_id = ?)
            AND c.id NOT IN (SELECT categoria_id FROM presupuestos
                              WHERE usuario_id = ? AND anio = ? AND mes = ?)
          ORDER BY c.nombre",
        'issiiii',
        [$usuarioId, $periodo['desde'], $periodo['hasta'], $usuarioId, $usuarioId, $periodo['anio'], $periodo['mes']]
    );
}

/** ¿El monto es un número positivo con máximo 2 decimales? */
function monto_valido(string $monto): bool
{
    return preg_match('/^\d+(\.\d{1,2})?$/', $monto) && (float) $monto > 0 && (float) $monto <= 9999999999.99;
}

/** Procesa crear / editar / eliminar / copiar y redirige. */
function procesar_presupuestos(mysqli $cx, int $usuarioId, array $periodo, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $monto  = trim((string) ($_POST['monto_limite'] ?? ''));

    try {
        switch ($accion) {
            case 'crear':
                $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
                if (!monto_valido($monto)) {
                    flash('El límite debe ser un monto mayor a 0 con máximo 2 decimales.', 'error');
                    break;
                }
                $categoria = consultar_fila(
                    $cx,
                    "SELECT nombre FROM categorias WHERE id = ? AND activo = 1 AND tipo = 'gasto'
                       AND (usuario_id IS NULL OR usuario_id = ?)",
                    'ii',
                    [$categoriaId, $usuarioId]
                );
                if (!$categoria) {
                    flash('Selecciona una categoría de gasto válida.', 'error');
                    break;
                }
                try {
                    ejecutar(
                        $cx,
                        'INSERT INTO presupuestos (usuario_id, categoria_id, anio, mes, monto_limite) VALUES (?, ?, ?, ?, ?)',
                        'iiiis',
                        [$usuarioId, $categoriaId, $periodo['anio'], $periodo['mes'], $monto]
                    );
                    flash("Presupuesto para «{$categoria['nombre']}» creado.", 'exito');
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() !== 1062) { // 1062 = registro duplicado (UNIQUE)
                        throw $e;
                    }
                    flash("«{$categoria['nombre']}» ya tiene presupuesto en este mes.", 'error');
                }
                break;

            case 'editar':
                if (!monto_valido($monto)) {
                    flash('El límite debe ser un monto mayor a 0 con máximo 2 decimales.', 'error');
                    break;
                }
                ejecutar(
                    $cx,
                    'UPDATE presupuestos SET monto_limite = ? WHERE id = ? AND usuario_id = ?',
                    'sii',
                    [$monto, $id, $usuarioId]
                );
                // affected_rows = 0 también si el monto no cambió, por eso se verifica que exista
                $existe = consultar_fila($cx, 'SELECT id FROM presupuestos WHERE id = ? AND usuario_id = ?', 'ii', [$id, $usuarioId]);
                flash($existe ? 'Presupuesto actualizado.' : 'El presupuesto no existe.', $existe ? 'exito' : 'error');
                break;

            case 'eliminar':
                // Con consultas preparadas, las filas afectadas se leen del statement
                $stmt     = ejecutar($cx, 'DELETE FROM presupuestos WHERE id = ? AND usuario_id = ?', 'ii', [$id, $usuarioId]);
                $borrados = mysqli_stmt_affected_rows($stmt) > 0;
                flash($borrados ? 'Presupuesto eliminado.' : 'El presupuesto no existe.', $borrados ? 'exito' : 'error');
                break;

            case 'copiar':
                // Copia los presupuestos del mes anterior (los que ya existan no se duplican)
                $anterior = leer_mes(['mes' => $periodo['anterior']]);
                $stmt = ejecutar(
                    $cx,
                    'INSERT IGNORE INTO presupuestos (usuario_id, categoria_id, anio, mes, monto_limite)
                     SELECT p.usuario_id, p.categoria_id, ?, ?, p.monto_limite
                       FROM presupuestos p
                       JOIN categorias c ON c.id = p.categoria_id AND c.activo = 1
                      WHERE p.usuario_id = ? AND p.anio = ? AND p.mes = ?',
                    'iiiii',
                    [$periodo['anio'], $periodo['mes'], $usuarioId, $anterior['anio'], $anterior['mes']]
                );
                $copiados = max(0, mysqli_stmt_affected_rows($stmt));
                flash(
                    $copiados > 0 ? "Se copiaron $copiados presupuesto(s) de {$anterior['etiqueta']}." : "No había presupuestos nuevos que copiar de {$anterior['etiqueta']}.",
                    $copiados > 0 ? 'exito' : 'info'
                );
                break;

            default:
                flash('Acción no válida.', 'error');
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Error en presupuestos: ' . $e->getMessage());
        flash('Ocurrió un error al guardar el presupuesto.', 'error');
    }

    redirigir($volver);
}
