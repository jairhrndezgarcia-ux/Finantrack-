<?php
// includes/metas_crud.php
// Módulo 5 (parte 2): metas de ahorro con fecha límite.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/presupuestos_crud.php'; // monto_valido()

/** Metas del usuario con su progreso calculado. Primero las pendientes. */
function obtener_metas(mysqli $cx, int $usuarioId): array
{
    $metas = consultar_filas(
        $cx,
        'SELECT id, nombre, monto_objetivo, monto_actual, fecha_limite, creado_en
           FROM metas_ahorro WHERE usuario_id = ?
          ORDER BY (monto_actual >= monto_objetivo), fecha_limite',
        'i',
        [$usuarioId]
    );

    $hoy = new DateTime('today');
    foreach ($metas as &$m) {
        $objetivo = (float) $m['monto_objetivo'];
        $actual   = (float) $m['monto_actual'];
        $limite   = new DateTime($m['fecha_limite']);

        $m['completada'] = $actual >= $objetivo;
        $m['faltante']   = max(0, $objetivo - $actual);
        $m['porcentaje'] = $objetivo > 0 ? $actual / $objetivo * 100 : 0;
        $m['vencida']    = !$m['completada'] && $limite < $hoy;
        $m['dias']       = (int) $hoy->diff($limite)->format('%r%a'); // negativo si ya pasó

        // Ahorro mensual sugerido para llegar a tiempo (mínimo 1 mes)
        $meses = max(1, (int) ceil($m['dias'] / 30));
        $m['ahorro_mensual'] = $m['completada'] || $m['vencida'] ? 0 : $m['faltante'] / $meses;
    }
    unset($m);
    return $metas;
}

/** Valida nombre, objetivo y fecha. En alta la fecha no puede ser pasada. */
function validar_meta(array $datos, bool $esNueva): array|string
{
    $nombre   = trim((string) ($datos['nombre'] ?? ''));
    $objetivo = trim((string) ($datos['monto_objetivo'] ?? ''));
    $fecha    = (string) ($datos['fecha_limite'] ?? '');
    $inicial  = trim((string) ($datos['monto_actual'] ?? '0'));

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        return 'El nombre es obligatorio y debe tener máximo 80 caracteres.';
    }
    if (!monto_valido($objetivo)) {
        return 'El objetivo debe ser un monto mayor a 0 con máximo 2 decimales.';
    }
    if (!fecha_valida_meta($fecha)) {
        return 'La fecha límite no es válida.';
    }
    if ($esNueva && $fecha < date('Y-m-d')) {
        return 'La fecha límite no puede estar en el pasado.';
    }
    if ($esNueva && $inicial !== '' && $inicial !== '0' && !monto_valido($inicial)) {
        return 'El ahorro inicial debe ser un monto válido.';
    }

    return [
        'nombre'   => $nombre,
        'objetivo' => $objetivo,
        'fecha'    => $fecha,
        'inicial'  => $inicial === '' ? '0' : $inicial,
    ];
}

function fecha_valida_meta(string $fecha): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha && $fecha <= '2100-12-31';
}

/** Procesa crear / editar / abonar / retirar / eliminar y redirige. */
function procesar_metas(mysqli $cx, int $usuarioId, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        // Todas las acciones excepto "crear" trabajan sobre una meta del usuario
        $meta = null;
        if ($accion !== 'crear') {
            $meta = consultar_fila(
                $cx,
                'SELECT id, nombre, monto_objetivo, monto_actual FROM metas_ahorro WHERE id = ? AND usuario_id = ?',
                'ii',
                [$id, $usuarioId]
            );
            if (!$meta) {
                flash('La meta no existe.', 'error');
                redirigir($volver);
            }
        }

        switch ($accion) {
            case 'crear':
            case 'editar':
                $datos = validar_meta($_POST, $accion === 'crear');
                if (is_string($datos)) {
                    flash($datos, 'error');
                    break;
                }
                if ($accion === 'crear') {
                    ejecutar(
                        $cx,
                        'INSERT INTO metas_ahorro (usuario_id, nombre, monto_objetivo, monto_actual, fecha_limite)
                         VALUES (?, ?, ?, ?, ?)',
                        'issss',
                        [$usuarioId, $datos['nombre'], $datos['objetivo'], $datos['inicial'], $datos['fecha']]
                    );
                    flash("Meta «{$datos['nombre']}» creada. ¡A ahorrar!", 'exito');
                } else {
                    ejecutar(
                        $cx,
                        'UPDATE metas_ahorro SET nombre = ?, monto_objetivo = ?, fecha_limite = ? WHERE id = ? AND usuario_id = ?',
                        'sssii',
                        [$datos['nombre'], $datos['objetivo'], $datos['fecha'], $id, $usuarioId]
                    );
                    flash("Meta «{$datos['nombre']}» actualizada.", 'exito');
                }
                break;

            case 'abonar':
            case 'retirar':
                $monto = trim((string) ($_POST['monto'] ?? ''));
                if (!monto_valido($monto)) {
                    flash('Escribe un monto mayor a 0 con máximo 2 decimales.', 'error');
                    break;
                }
                if ($accion === 'retirar' && (float) $monto > (float) $meta['monto_actual']) {
                    flash('No puedes retirar más de lo ahorrado en la meta.', 'error');
                    break;
                }
                $signo = $accion === 'abonar' ? '+' : '-';
                ejecutar(
                    $cx,
                    "UPDATE metas_ahorro SET monto_actual = monto_actual $signo ? WHERE id = ? AND usuario_id = ?",
                    'sii',
                    [$monto, $id, $usuarioId]
                );

                $nuevo = (float) $meta['monto_actual'] + ($accion === 'abonar' ? (float) $monto : -(float) $monto);
                if ($accion === 'abonar' && $nuevo >= (float) $meta['monto_objetivo']
                    && (float) $meta['monto_actual'] < (float) $meta['monto_objetivo']) {
                    flash("🎉 ¡Felicidades! Completaste la meta «{$meta['nombre']}».", 'exito');
                } else {
                    flash($accion === 'abonar' ? 'Abono registrado.' : 'Retiro registrado.', 'exito');
                }
                break;

            case 'eliminar':
                ejecutar($cx, 'DELETE FROM metas_ahorro WHERE id = ? AND usuario_id = ?', 'ii', [$id, $usuarioId]);
                flash("Meta «{$meta['nombre']}» eliminada.", 'exito');
                break;

            default:
                flash('Acción no válida.', 'error');
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Error en metas: ' . $e->getMessage());
        flash('Ocurrió un error al guardar la meta.', 'error');
    }

    redirigir($volver);
}
