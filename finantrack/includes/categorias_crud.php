<?php
// includes/categorias_crud.php
// Lógica compartida del CRUD de categorías (Módulo 3).
//
// La usan dos páginas con un "propietario" distinto:
//   categorias.php        -> $propietario = id del usuario (categorías privadas)
//   admin/categorias.php  -> $propietario = null (categorías globales)

require_once __DIR__ . '/db.php';

// Íconos permitidos (el usuario elige uno del selector)
const ICONOS_CATEGORIA = [
    '💰', '💼', '🛒', '💵', '🍽️', '🏠', '🚌', '🚗', '💡', '🩺',
    '🎬', '📚', '👕', '🎁', '✈️', '🐾', '📱', '🏋️', '☕', '🧾',
];

/**
 * Condición SQL para limitar las consultas a las categorías del propietario.
 * Devuelve [fragmento SQL, tipos para bind_param, parámetros].
 */
function condicion_propietario(?int $propietario): array
{
    return $propietario === null
        ? ['usuario_id IS NULL', '', []]
        : ['usuario_id = ?', 'i', [$propietario]];
}

/**
 * Lectura: categorías activas que ve el propietario.
 *   Usuario: las globales (solo lectura) + las suyas (editables).
 *   Admin:   solo las globales (todas editables).
 * Incluye cuántos movimientos y presupuestos usan cada categoría.
 */
function obtener_categorias(mysqli $cx, ?int $propietario): array
{
    if ($propietario === null) {
        $sql = 'SELECT c.*,
                       (SELECT COUNT(*) FROM transacciones t WHERE t.categoria_id = c.id) +
                       (SELECT COUNT(*) FROM presupuestos p WHERE p.categoria_id = c.id) AS usos
                  FROM categorias c
                 WHERE c.activo = 1 AND c.usuario_id IS NULL
                 ORDER BY c.tipo DESC, c.nombre';
        $stmt = ejecutar($cx, $sql);
    } else {
        $sql = 'SELECT c.*,
                       (SELECT COUNT(*) FROM transacciones t WHERE t.categoria_id = c.id AND t.usuario_id = ?) +
                       (SELECT COUNT(*) FROM presupuestos p WHERE p.categoria_id = c.id AND p.usuario_id = ?) AS usos
                  FROM categorias c
                 WHERE c.activo = 1 AND (c.usuario_id IS NULL OR c.usuario_id = ?)
                 ORDER BY c.tipo DESC, c.usuario_id IS NULL DESC, c.nombre';
        $stmt = ejecutar($cx, $sql, 'iii', [$propietario, $propietario, $propietario]);
    }

    $categorias = [];
    $resultado  = mysqli_stmt_get_result($stmt);
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // El usuario no puede modificar las categorías globales
        $fila['editable'] = $propietario === null || $fila['usuario_id'] !== null;
        $fila['usos']     = (int) $fila['usos'];
        $categorias[]     = $fila;
    }
    return $categorias;
}

/** Selección: una categoría del propietario por id (null si no existe o no es suya). */
function obtener_categoria_propia(mysqli $cx, ?int $propietario, int $id): ?array
{
    [$condicion, $tipos, $params] = condicion_propietario($propietario);
    $stmt = ejecutar(
        $cx,
        "SELECT id, nombre, tipo FROM categorias WHERE id = ? AND activo = 1 AND $condicion",
        'i' . $tipos,
        array_merge([$id], $params)
    );
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

/** Cuántos movimientos y presupuestos están ligados a la categoría. */
function contar_usos(mysqli $cx, int $id): int
{
    $stmt = ejecutar(
        $cx,
        'SELECT (SELECT COUNT(*) FROM transacciones WHERE categoria_id = ?) +
                (SELECT COUNT(*) FROM presupuestos  WHERE categoria_id = ?) AS usos',
        'ii',
        [$id, $id]
    );
    return (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['usos'];
}

/** Valida los datos del formulario. Devuelve el arreglo limpio o un mensaje de error. */
function validar_categoria(array $datos): array|string
{
    $nombre = trim($datos['nombre'] ?? '');
    $tipo   = $datos['tipo'] ?? '';
    $color  = strtolower($datos['color'] ?? '');
    $icono  = $datos['icono'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 50) {
        return 'El nombre es obligatorio y debe tener máximo 50 caracteres.';
    }
    if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
        return 'Selecciona si la categoría es de ingreso o de gasto.';
    }
    if (!preg_match('/^#[0-9a-f]{6}$/', $color)) {
        return 'El color no es válido.';
    }
    if (!in_array($icono, ICONOS_CATEGORIA, true)) {
        return 'Selecciona un ícono de la lista.';
    }
    return ['nombre' => $nombre, 'tipo' => $tipo, 'color' => $color, 'icono' => $icono];
}

/** ¿Ya existe otra categoría visible con el mismo nombre y tipo? */
function categoria_duplicada(mysqli $cx, ?int $propietario, string $nombre, string $tipo, int $excluirId): bool
{
    // El usuario no puede repetir el nombre de una global ni de una suya
    $alcance = $propietario === null ? 'usuario_id IS NULL' : '(usuario_id IS NULL OR usuario_id = ?)';
    $tipos   = 'ssi' . ($propietario === null ? '' : 'i');
    $params  = [$nombre, $tipo, $excluirId];
    if ($propietario !== null) {
        $params[] = $propietario;
    }

    $stmt = ejecutar(
        $cx,
        "SELECT id FROM categorias WHERE nombre = ? AND tipo = ? AND activo = 1 AND id <> ? AND $alcance",
        $tipos,
        $params
    );
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
}

/**
 * Procesa el formulario enviado (alta, modificación o baja), deja un
 * mensaje flash y redirige a $volver (patrón POST -> Redirect -> GET).
 */
function procesar_categorias(mysqli $cx, ?int $propietario, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        // ---------- BAJA ----------
        if ($accion === 'eliminar') {
            $categoria = obtener_categoria_propia($cx, $propietario, $id);
            if (!$categoria) {
                flash('La categoría no existe o no puedes eliminarla.', 'error');
                redirigir($volver);
            }

            $usos = contar_usos($cx, $id);
            if ($usos > 0) {
                flash("No se puede eliminar «{$categoria['nombre']}»: tiene $usos movimiento(s) o presupuesto(s) ligados.", 'error');
                redirigir($volver);
            }

            [$condicion, $tipos, $params] = condicion_propietario($propietario);
            ejecutar($cx, "DELETE FROM categorias WHERE id = ? AND $condicion", 'i' . $tipos, array_merge([$id], $params));
            flash("Categoría «{$categoria['nombre']}» eliminada.", 'exito');
            redirigir($volver);
        }

        // ---------- ALTA y MODIFICACIÓN ----------
        if ($accion !== 'crear' && $accion !== 'editar') {
            flash('Acción no válida.', 'error');
            redirigir($volver);
        }

        $datos = validar_categoria($_POST);
        if (is_string($datos)) {
            flash($datos, 'error');
            redirigir($volver);
        }

        $excluir = $accion === 'editar' ? $id : 0;
        if (categoria_duplicada($cx, $propietario, $datos['nombre'], $datos['tipo'], $excluir)) {
            flash("Ya existe una categoría de {$datos['tipo']} llamada «{$datos['nombre']}».", 'error');
            redirigir($volver);
        }

        if ($accion === 'crear') {
            ejecutar(
                $cx,
                'INSERT INTO categorias (usuario_id, nombre, tipo, color, icono) VALUES (?, ?, ?, ?, ?)',
                'issss',
                [$propietario, $datos['nombre'], $datos['tipo'], $datos['color'], $datos['icono']]
            );
            flash("Categoría «{$datos['nombre']}» creada.", 'exito');
            redirigir($volver);
        }

        // Editar: verificar que la categoría sea del propietario
        $categoria = obtener_categoria_propia($cx, $propietario, $id);
        if (!$categoria) {
            flash('La categoría no existe o no puedes modificarla.', 'error');
            redirigir($volver);
        }
        // Cambiar el tipo dejaría inconsistentes los movimientos ya registrados
        if ($categoria['tipo'] !== $datos['tipo'] && contar_usos($cx, $id) > 0) {
            flash('No puedes cambiar el tipo de una categoría que ya tiene movimientos.', 'error');
            redirigir($volver);
        }

        [$condicion, $tipos, $params] = condicion_propietario($propietario);
        ejecutar(
            $cx,
            "UPDATE categorias SET nombre = ?, tipo = ?, color = ?, icono = ? WHERE id = ? AND $condicion",
            'ssssi' . $tipos,
            array_merge([$datos['nombre'], $datos['tipo'], $datos['color'], $datos['icono'], $id], $params)
        );
        flash("Categoría «{$datos['nombre']}» actualizada.", 'exito');
        redirigir($volver);

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1451) { // llave foránea: tiene registros ligados
            flash('No se puede eliminar: la categoría tiene registros ligados.', 'error');
        } else {
            error_log('Error en categorías: ' . $e->getMessage());
            flash('Ocurrió un error al guardar la categoría.', 'error');
        }
        redirigir($volver);
    }
}
