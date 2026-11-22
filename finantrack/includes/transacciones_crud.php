<?php
// includes/transacciones_crud.php
// Lógica del Módulo 4: Gestor de Transacciones Financieras.

require_once __DIR__ . '/db.php';

const MONTO_MAXIMO = 9999999999.99; // DECIMAL(12,2)

/* ===================================================
   CATÁLOGOS PARA LOS FORMULARIOS
   =================================================== */

/** Categorías que el usuario puede usar: globales + las suyas. */
function categorias_disponibles(mysqli $cx, int $usuarioId): array
{
    return consultar_filas(
        $cx,
        'SELECT id, nombre, tipo, icono, color FROM categorias
          WHERE activo = 1 AND (usuario_id IS NULL OR usuario_id = ?)
          ORDER BY tipo DESC, nombre',
        'i',
        [$usuarioId]
    );
}

/** Métodos de pago activos (catálogo del administrador). */
function metodos_pago_activos(mysqli $cx): array
{
    return consultar_filas($cx, 'SELECT id, nombre FROM metodos_pago WHERE activo = 1 ORDER BY id');
}

/** Divisa base del usuario (para mostrar el símbolo en los montos). */
function divisa_usuario(mysqli $cx, int $usuarioId): array
{
    return consultar_fila(
        $cx,
        'SELECT d.codigo, d.simbolo FROM usuarios u JOIN divisas d ON d.id = u.divisa_id WHERE u.id = ?',
        'i',
        [$usuarioId]
    ) ?? ['codigo' => 'MXN', 'simbolo' => '$'];
}

/* ===================================================
   FILTROS (rango de fechas, tipo y categoría)
   =================================================== */

/** ¿La cadena es una fecha real con formato AAAA-MM-DD? */
function fecha_valida(string $fecha): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

/** Lee los filtros de la URL; si faltan o son inválidos usa el mes actual. */
function leer_filtros(array $get): array
{
    $desde = (string) ($get['desde'] ?? '');
    $hasta = (string) ($get['hasta'] ?? '');

    $filtros = [
        'desde'     => fecha_valida($desde) ? $desde : date('Y-m-01'),
        'hasta'     => fecha_valida($hasta) ? $hasta : date('Y-m-t'),
        'tipo'      => in_array($get['tipo'] ?? '', ['ingreso', 'gasto'], true) ? $get['tipo'] : '',
        'categoria' => max(0, (int) ($get['categoria'] ?? 0)),
    ];

    // Si el rango viene al revés, se intercambia
    if ($filtros['desde'] > $filtros['hasta']) {
        [$filtros['desde'], $filtros['hasta']] = [$filtros['hasta'], $filtros['desde']];
    }
    return $filtros;
}

/** Condición WHERE común para la tabla y los totales. */
function where_filtros(int $usuarioId, array $f): array
{
    $sql    = 't.usuario_id = ? AND t.activo = 1 AND t.fecha BETWEEN ? AND ?';
    $tipos  = 'iss';
    $params = [$usuarioId, $f['desde'], $f['hasta']];

    if ($f['tipo'] !== '') {
        $sql .= ' AND t.tipo = ?';
        $tipos .= 's';
        $params[] = $f['tipo'];
    }
    if ($f['categoria'] > 0) {
        $sql .= ' AND t.categoria_id = ?';
        $tipos .= 'i';
        $params[] = $f['categoria'];
    }
    return [$sql, $tipos, $params];
}

/* ===================================================
   LECTURA
   =================================================== */

/** Transacciones activas del usuario que cumplen los filtros. */
function obtener_transacciones(mysqli $cx, int $usuarioId, array $filtros): array
{
    [$where, $tipos, $params] = where_filtros($usuarioId, $filtros);
    return consultar_filas(
        $cx,
        "SELECT t.id, t.tipo, t.monto, t.fecha, t.notas, t.categoria_id, t.metodo_pago_id,
                c.nombre AS categoria, c.icono, c.color, m.nombre AS metodo
           FROM transacciones t
           JOIN categorias   c ON c.id = t.categoria_id
           JOIN metodos_pago m ON m.id = t.metodo_pago_id
          WHERE $where
          ORDER BY t.fecha DESC, t.id DESC",
        $tipos,
        $params
    );
}

/** Ingresos, gastos y balance del periodo filtrado. */
function totales_periodo(mysqli $cx, int $usuarioId, array $filtros): array
{
    [$where, $tipos, $params] = where_filtros($usuarioId, $filtros);
    $fila = consultar_fila(
        $cx,
        "SELECT COALESCE(SUM(CASE WHEN t.tipo = 'ingreso' THEN t.monto END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN t.tipo = 'gasto'   THEN t.monto END), 0) AS gastos
           FROM transacciones t
          WHERE $where",
        $tipos,
        $params
    );
    $fila['balance'] = (float) $fila['ingresos'] - (float) $fila['gastos'];
    return $fila;
}

/** Saldo disponible histórico (todas las transacciones activas). */
function saldo_disponible(mysqli $cx, int $usuarioId): float
{
    $fila = consultar_fila(
        $cx,
        "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) AS saldo
           FROM transacciones WHERE usuario_id = ? AND activo = 1",
        'i',
        [$usuarioId]
    );
    return (float) $fila['saldo'];
}

/* ===================================================
   ALTA, MODIFICACIÓN Y BORRADO LÓGICO
   =================================================== */

/** Valida el formulario. Devuelve los datos limpios o un mensaje de error. */
function validar_transaccion(mysqli $cx, int $usuarioId, array $datos): array|string
{
    $tipo      = $datos['tipo'] ?? '';
    $monto     = trim((string) ($datos['monto'] ?? ''));
    $fecha     = (string) ($datos['fecha'] ?? '');
    $categoria = (int) ($datos['categoria_id'] ?? 0);
    $metodo    = (int) ($datos['metodo_pago_id'] ?? 0);
    $notas     = trim((string) ($datos['notas'] ?? ''));

    if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
        return 'Indica si es un ingreso o un gasto.';
    }
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $monto) || (float) $monto <= 0 || (float) $monto > MONTO_MAXIMO) {
        return 'El monto debe ser un número mayor a 0 con máximo 2 decimales.';
    }
    if (!fecha_valida($fecha)) {
        return 'La fecha no es válida.';
    }
    if ($fecha > date('Y-m-d')) {
        return 'No puedes registrar movimientos con fecha futura.';
    }
    if (mb_strlen($notas) > 255) {
        return 'Las notas deben tener máximo 255 caracteres.';
    }

    // La categoría debe estar disponible para el usuario y ser del mismo tipo
    $cat = consultar_fila(
        $cx,
        'SELECT tipo FROM categorias WHERE id = ? AND activo = 1 AND (usuario_id IS NULL OR usuario_id = ?)',
        'ii',
        [$categoria, $usuarioId]
    );
    if (!$cat) {
        return 'Selecciona una categoría válida.';
    }
    if ($cat['tipo'] !== $tipo) {
        return 'La categoría no corresponde al tipo de movimiento.';
    }

    if (!consultar_fila($cx, 'SELECT id FROM metodos_pago WHERE id = ? AND activo = 1', 'i', [$metodo])) {
        return 'Selecciona un método de pago válido.';
    }

    return [
        'tipo'      => $tipo,
        'monto'     => $monto,
        'fecha'     => $fecha,
        'categoria' => $categoria,
        'metodo'    => $metodo,
        'notas'     => $notas === '' ? null : $notas,
    ];
}

/** ¿La transacción existe, está activa y es del usuario? */
function transaccion_del_usuario(mysqli $cx, int $usuarioId, int $id): bool
{
    return consultar_fila(
        $cx,
        'SELECT id FROM transacciones WHERE id = ? AND usuario_id = ? AND activo = 1',
        'ii',
        [$id, $usuarioId]
    ) !== null;
}

/** Procesa el formulario (crear, editar, eliminar) y redirige (POST -> Redirect -> GET). */
function procesar_transacciones(mysqli $cx, int $usuarioId, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        // ---------- BORRADO LÓGICO ----------
        // No se elimina la fila: se marca activo = 0 y deja de contar en el saldo.
        if ($accion === 'eliminar') {
            if (!transaccion_del_usuario($cx, $usuarioId, $id)) {
                flash('El movimiento no existe o no puedes eliminarlo.', 'error');
                redirigir($volver);
            }
            ejecutar($cx, 'UPDATE transacciones SET activo = 0 WHERE id = ? AND usuario_id = ?', 'ii', [$id, $usuarioId]);
            flash('Movimiento eliminado. El saldo se recalculó.', 'exito');
            redirigir($volver);
        }

        if ($accion !== 'crear' && $accion !== 'editar') {
            flash('Acción no válida.', 'error');
            redirigir($volver);
        }

        $datos = validar_transaccion($cx, $usuarioId, $_POST);
        if (is_string($datos)) {
            flash($datos, 'error');
            redirigir($volver);
        }

        // ---------- ALTA ----------
        if ($accion === 'crear') {
            ejecutar(
                $cx,
                'INSERT INTO transacciones (usuario_id, categoria_id, metodo_pago_id, tipo, monto, fecha, notas)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'iiissss',
                [$usuarioId, $datos['categoria'], $datos['metodo'], $datos['tipo'], $datos['monto'], $datos['fecha'], $datos['notas']]
            );
            flash(($datos['tipo'] === 'ingreso' ? 'Ingreso' : 'Gasto') . ' registrado correctamente.', 'exito');
            redirigir($volver);
        }

        // ---------- MODIFICACIÓN ----------
        if (!transaccion_del_usuario($cx, $usuarioId, $id)) {
            flash('El movimiento no existe o no puedes modificarlo.', 'error');
            redirigir($volver);
        }
        ejecutar(
            $cx,
            'UPDATE transacciones
                SET categoria_id = ?, metodo_pago_id = ?, tipo = ?, monto = ?, fecha = ?, notas = ?
              WHERE id = ? AND usuario_id = ?',
            'iissssii',
            [$datos['categoria'], $datos['metodo'], $datos['tipo'], $datos['monto'], $datos['fecha'], $datos['notas'], $id, $usuarioId]
        );
        flash('Movimiento actualizado.', 'exito');
        redirigir($volver);

    } catch (mysqli_sql_exception $e) {
        error_log('Error en transacciones: ' . $e->getMessage());
        flash('Ocurrió un error al guardar el movimiento.', 'error');
        redirigir($volver);
    }
}
