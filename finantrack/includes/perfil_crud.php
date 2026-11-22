<?php
// includes/perfil_crud.php
// Módulo 2 (perfil): consulta y actualización de la cuenta del usuario.

require_once __DIR__ . '/db.php';

/** Datos de la cuenta del usuario con su divisa y rol. */
function obtener_perfil(mysqli $cx, int $usuarioId): ?array
{
    return consultar_fila(
        $cx,
        'SELECT u.id, u.nombre, u.apellidos, u.correo, u.divisa_id, u.rol_id,
                u.ultimo_acceso, u.creado_en, d.codigo AS divisa, r.nombre AS rol
           FROM usuarios u
           JOIN divisas d ON d.id = u.divisa_id
           JOIN roles   r ON r.id = u.rol_id
          WHERE u.id = ?',
        'i',
        [$usuarioId]
    );
}

/** Divisas que puede elegir: las activas y la que ya tiene (aunque esté inactiva). */
function divisas_para_perfil(mysqli $cx, int $divisaActual): array
{
    return consultar_filas(
        $cx,
        'SELECT id, codigo, nombre, simbolo FROM divisas WHERE activo = 1 OR id = ? ORDER BY id',
        'i',
        [$divisaActual]
    );
}

/** Resumen de la actividad del usuario. */
function estadisticas_perfil(mysqli $cx, int $usuarioId): array
{
    return consultar_fila(
        $cx,
        'SELECT (SELECT COUNT(*) FROM transacciones WHERE usuario_id = ? AND activo = 1) AS movimientos,
                (SELECT COUNT(*) FROM categorias    WHERE usuario_id = ?)                AS categorias,
                (SELECT COUNT(*) FROM metas_ahorro  WHERE usuario_id = ?)                AS metas',
        'iii',
        [$usuarioId, $usuarioId, $usuarioId]
    );
}

/** ¿La contraseña escrita es la actual del usuario? */
function verificar_password_actual(mysqli $cx, int $usuarioId, string $password): bool
{
    $fila = consultar_fila($cx, 'SELECT password_hash FROM usuarios WHERE id = ?', 'i', [$usuarioId]);
    return $fila !== null && password_verify($password, $fila['password_hash']);
}

/* ===================================================
   ACTUALIZAR DATOS PERSONALES
   =================================================== */
function actualizar_datos(mysqli $cx, int $usuarioId, array $perfil, array $post): void
{
    $nombre    = trim((string) ($post['nombre'] ?? ''));
    $apellidos = trim((string) ($post['apellidos'] ?? ''));
    $correo    = strtolower(trim((string) ($post['correo'] ?? '')));
    $divisaId  = (int) ($post['divisa_id'] ?? 0);

    if ($nombre === '' || $apellidos === '') {
        flash('El nombre y los apellidos son obligatorios.', 'error');
        return;
    }
    if (mb_strlen($nombre) > 60 || mb_strlen($apellidos) > 80) {
        flash('El nombre o los apellidos son demasiado largos.', 'error');
        return;
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 120) {
        flash('El correo electrónico no es válido.', 'error');
        return;
    }

    // Cambiar el correo cambia el usuario con el que se inicia sesión:
    // se pide la contraseña actual para confirmar que es el dueño de la cuenta.
    if ($correo !== $perfil['correo']) {
        if (!verificar_password_actual($cx, $usuarioId, (string) ($post['password_confirmacion'] ?? ''))) {
            flash('Para cambiar el correo escribe tu contraseña actual correctamente.', 'error');
            return;
        }
        if (consultar_fila($cx, 'SELECT id FROM usuarios WHERE correo = ? AND id <> ?', 'si', [$correo, $usuarioId])) {
            flash('Ese correo ya está registrado en otra cuenta.', 'error');
            return;
        }
    }

    // La divisa debe estar activa (o ser la que ya tenía)
    $divisaValida = consultar_fila(
        $cx,
        'SELECT id FROM divisas WHERE id = ? AND (activo = 1 OR id = ?)',
        'ii',
        [$divisaId, (int) $perfil['divisa_id']]
    );
    if (!$divisaValida) {
        flash('Selecciona una divisa válida.', 'error');
        return;
    }

    ejecutar(
        $cx,
        'UPDATE usuarios SET nombre = ?, apellidos = ?, correo = ?, divisa_id = ? WHERE id = ?',
        'sssii',
        [$nombre, $apellidos, $correo, $divisaId, $usuarioId]
    );

    // Mantener la sesión al día (el nombre aparece en el menú)
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_correo'] = $correo;
    flash('Tus datos se actualizaron correctamente.', 'exito');
}

/* ===================================================
   CAMBIO SEGURO DE CONTRASEÑA
   =================================================== */
function cambiar_password(mysqli $cx, int $usuarioId, array $post): void
{
    $actual    = (string) ($post['password_actual'] ?? '');
    $nueva     = (string) ($post['password_nueva'] ?? '');
    $confirmar = (string) ($post['password_confirmar'] ?? '');

    if (!verificar_password_actual($cx, $usuarioId, $actual)) {
        flash('La contraseña actual no es correcta.', 'error');
        return;
    }
    if (strlen($nueva) < 6) {
        flash('La nueva contraseña debe tener al menos 6 caracteres.', 'error');
        return;
    }
    if ($nueva !== $confirmar) {
        flash('La nueva contraseña y su confirmación no coinciden.', 'error');
        return;
    }
    if ($nueva === $actual) {
        flash('La nueva contraseña debe ser diferente a la actual.', 'error');
        return;
    }

    ejecutar(
        $cx,
        'UPDATE usuarios SET password_hash = ? WHERE id = ?',
        'si',
        [password_hash($nueva, PASSWORD_DEFAULT), $usuarioId]
    );

    // Nuevo id de sesión después de cambiar credenciales
    session_regenerate_id(true);
    flash('Tu contraseña se cambió correctamente.', 'exito');
}

/** Procesa el formulario enviado y redirige al perfil. */
function procesar_perfil(mysqli $cx, int $usuarioId, array $perfil, string $volver): void
{
    if (!verificar_csrf()) {
        flash('La sesión expiró. Vuelve a intentarlo.', 'error');
        redirigir($volver);
    }

    try {
        match ($_POST['accion'] ?? '') {
            'datos'    => actualizar_datos($cx, $usuarioId, $perfil, $_POST),
            'password' => cambiar_password($cx, $usuarioId, $_POST),
            default    => flash('Acción no válida.', 'error'),
        };
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) { // correo duplicado (UNIQUE)
            flash('Ese correo ya está registrado en otra cuenta.', 'error');
        } else {
            error_log('Error en perfil: ' . $e->getMessage());
            flash('Ocurrió un error al guardar los cambios.', 'error');
        }
    }
    redirigir($volver);
}
