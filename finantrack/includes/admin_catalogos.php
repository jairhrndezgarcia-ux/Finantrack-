<?php
// includes/admin_catalogos.php
// Catálogos que gestiona el administrador: métodos de pago, divisas y usuarios.

require_once __DIR__ . '/db.php';

/* ===================================================
   LECTURA
   =================================================== */

/** Métodos de pago con cuántos movimientos los usan. */
function obtener_metodos(mysqli $cx): array
{
    return consultar_filas(
        $cx,
        'SELECT m.id, m.nombre, m.activo,
                (SELECT COUNT(*) FROM transacciones t WHERE t.metodo_pago_id = m.id) AS usos
           FROM metodos_pago m
          ORDER BY m.activo DESC, m.nombre'
    );
}

/** Divisas con cuántos usuarios las tienen como divisa base. */
function obtener_divisas(mysqli $cx): array
{
    return consultar_filas(
        $cx,
        'SELECT d.id, d.codigo, d.nombre, d.simbolo, d.activo,
                (SELECT COUNT(*) FROM usuarios u WHERE u.divisa_id = d.id) AS usos
           FROM divisas d
          ORDER BY d.activo DESC, d.codigo'
    );
}

/** Usuarios registrados (rol 2) con su actividad. */
function obtener_usuarios(mysqli $cx): array
{
    return consultar_filas(
        $cx,
        'SELECT u.id, u.nombre, u.apellidos, u.correo, u.activo, u.ultimo_acceso, u.creado_en,
                d.codigo AS divisa,
                (SELECT COUNT(*) FROM transacciones t WHERE t.usuario_id = u.id AND t.activo = 1) AS movimientos
           FROM usuarios u
           JOIN divisas d ON d.id = u.divisa_id
          WHERE u.rol_id = ?
          ORDER BY u.creado_en DESC',
        'i',
        [ROL_USUARIO]
    );
}

/* ===================================================
   VALIDACIONES
   =================================================== */

function validar_metodo(array $datos): array|string
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    if ($nombre === '' || mb_strlen($nombre) > 40) {
        return 'El nombre es obligatorio y debe tener máximo 40 caracteres.';
    }
    return ['nombre' => $nombre];
}

function validar_divisa(array $datos): array|string
{
    $codigo  = strtoupper(trim((string) ($datos['codigo'] ?? '')));
    $nombre  = trim((string) ($datos['nombre'] ?? ''));
    $simbolo = trim((string) ($datos['simbolo'] ?? ''));

    if (!preg_match('/^[A-Z]{3}$/', $codigo)) {
        return 'El código debe tener exactamente 3 letras (ej. MXN, USD).';
    }
    if ($nombre === '' || mb_strlen($nombre) > 50) {
        return 'El nombre es obligatorio y debe tener máximo 50 caracteres.';
    }
    if ($simbolo === '' || mb_strlen($simbolo) > 5) {
        return 'El símbolo es obligatorio y debe tener máximo 5 caracteres.';
    }
    return ['codigo' => $codigo, 'nombre' => $nombre, 'simbolo' => $simbolo];
}

/* ===================================================
   PROCESAMIENTO GENÉRICO DE UN CATÁLOGO
   =================================================== */

/**
 * Alta, modificación, activar/desactivar y baja de un catálogo simple.
 * $config describe la tabla:
 *   tabla, etiqueta, validar (función), columnas (para INSERT/UPDATE),
 *   tipos (para bind_param), uso_sql (cuenta registros ligados), uso_texto
 */
function procesar_catalogo(mysqli $cx, array $config, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    $tabla    = $config['tabla'];
    $o        = $config['genero'];               // terminación: 'o' o 'a'
    $un       = $o === 'a' ? 'una' : 'un';
    $el       = $o === 'a' ? 'La' : 'El';
    $etiqueta = $config['etiqueta'];
    $accion   = $_POST['accion'] ?? '';
    $id       = (int) ($_POST['id'] ?? 0);

    try {
        // Para editar, cambiar estado o eliminar, el registro debe existir
        $registro = null;
        if ($accion !== 'crear') {
            $registro = consultar_fila($cx, "SELECT * FROM $tabla WHERE id = ?", 'i', [$id]);
            if (!$registro) {
                flash("$el $etiqueta no existe.", 'error');
                redirigir($volver);
            }
        }

        switch ($accion) {
            case 'crear':
            case 'editar':
                $datos = $config['validar']($_POST);
                if (is_string($datos)) {
                    flash($datos, 'error');
                    break;
                }
                $valores = array_values($datos);
                $columnas = array_keys($datos);

                if ($accion === 'crear') {
                    $lista = implode(', ', $columnas);
                    $marcas = implode(', ', array_fill(0, count($columnas), '?'));
                    ejecutar($cx, "INSERT INTO $tabla ($lista) VALUES ($marcas)", $config['tipos'], $valores);
                    flash(ucfirst($etiqueta) . " agregad$o.", 'exito');
                } else {
                    $asignaciones = implode(', ', array_map(fn ($c) => "$c = ?", $columnas));
                    ejecutar($cx, "UPDATE $tabla SET $asignaciones WHERE id = ?", $config['tipos'] . 'i', [...$valores, $id]);
                    flash(ucfirst($etiqueta) . " actualizad$o.", 'exito');
                }
                break;

            case 'estado':
                $nuevo = (int) ($_POST['activo'] ?? 0) === 1 ? 1 : 0;
                // Siempre debe quedar al menos uno activo (registro y movimientos lo necesitan)
                if ($nuevo === 0) {
                    $otros = consultar_fila($cx, "SELECT COUNT(*) AS n FROM $tabla WHERE activo = 1 AND id <> ?", 'i', [$id]);
                    if ((int) $otros['n'] === 0) {
                        flash("Debe quedar al menos $un $etiqueta activ$o.", 'error');
                        break;
                    }
                }
                ejecutar($cx, "UPDATE $tabla SET activo = ? WHERE id = ?", 'ii', [$nuevo, $id]);
                flash(ucfirst($etiqueta) . ($nuevo ? " activad$o." : " desactivad$o. Ya no aparecerá en los formularios."), 'exito');
                break;

            case 'eliminar':
                $usos = (int) consultar_fila($cx, $config['uso_sql'], 'i', [$id])['n'];
                if ($usos > 0) {
                    flash("No se puede eliminar: lo usan $usos {$config['uso_texto']}. Puedes desactivarl$o.", 'error');
                    break;
                }
                if ((int) $registro['activo'] === 1) {
                    $otros = consultar_fila($cx, "SELECT COUNT(*) AS n FROM $tabla WHERE activo = 1 AND id <> ?", 'i', [$id]);
                    if ((int) $otros['n'] === 0) {
                        flash("Debe quedar al menos $un $etiqueta activ$o.", 'error');
                        break;
                    }
                }
                ejecutar($cx, "DELETE FROM $tabla WHERE id = ?", 'i', [$id]);
                flash(ucfirst($etiqueta) . " eliminad$o.", 'exito');
                break;

            default:
                flash('Acción no válida.', 'error');
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {        // UNIQUE: nombre o código repetido
            flash("Ya existe $un $etiqueta con ese " . ($tabla === 'divisas' ? 'código' : 'nombre') . '.', 'error');
        } elseif ($e->getCode() === 1451) {  // llave foránea: tiene registros ligados
            flash("No se puede eliminar: tiene registros ligados.", 'error');
        } else {
            error_log("Error en $tabla: " . $e->getMessage());
            flash('Ocurrió un error al guardar.', 'error');
        }
    }

    redirigir($volver);
}

/** Configuración de cada catálogo para procesar_catalogo(). */
function config_metodos(): array
{
    return [
        'tabla'     => 'metodos_pago',
        'etiqueta'  => 'método de pago',
        'validar'   => 'validar_metodo',
        'tipos'     => 's',
        'uso_sql'   => 'SELECT COUNT(*) AS n FROM transacciones WHERE metodo_pago_id = ?',
        'uso_texto' => 'movimiento(s)',
        'genero'    => 'o',
    ];
}

function config_divisas(): array
{
    return [
        'tabla'     => 'divisas',
        'etiqueta'  => 'divisa',
        'validar'   => 'validar_divisa',
        'tipos'     => 'sss',
        'uso_sql'   => 'SELECT COUNT(*) AS n FROM usuarios WHERE divisa_id = ?',
        'uso_texto' => 'usuario(s)',
        'genero'    => 'a',
    ];
}

/* ===================================================
   USUARIOS: activar / desactivar cuentas
   =================================================== */
function procesar_usuarios(mysqli $cx, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    $id    = (int) ($_POST['id'] ?? 0);
    $nuevo = (int) ($_POST['activo'] ?? 0) === 1 ? 1 : 0;

    if (($_POST['accion'] ?? '') !== 'estado') {
        flash('Acción no válida.', 'error');
        redirigir($volver);
    }

    try {
        // Solo cuentas de usuario: el administrador no puede desactivarse a sí mismo
        $usuario = consultar_fila($cx, 'SELECT nombre FROM usuarios WHERE id = ? AND rol_id = ?', 'ii', [$id, ROL_USUARIO]);
        if (!$usuario) {
            flash('El usuario no existe.', 'error');
            redirigir($volver);
        }
        ejecutar($cx, 'UPDATE usuarios SET activo = ? WHERE id = ? AND rol_id = ?', 'iii', [$nuevo, $id, ROL_USUARIO]);
        flash(
            $nuevo ? "Cuenta de {$usuario['nombre']} activada." : "Cuenta de {$usuario['nombre']} desactivada. Ya no podrá iniciar sesión.",
            'exito'
        );
    } catch (mysqli_sql_exception $e) {
        error_log('Error en usuarios: ' . $e->getMessage());
        flash('Ocurrió un error al actualizar la cuenta.', 'error');
    }
    redirigir($volver);
}
