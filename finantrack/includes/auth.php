<?php
// includes/auth.php
// Manejo de sesiones, roles y mensajes. Se incluye al inicio de cada página.

if (session_status() === PHP_SESSION_NONE) {
    // La cookie de sesión no es accesible desde JavaScript (httponly)
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Roles (coinciden con la tabla `roles`)
const ROL_ADMIN   = 1;
const ROL_USUARIO = 2;

/** Escapa texto antes de imprimirlo en HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** ¿Hay un usuario con sesión iniciada? */
function usuario_autenticado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** ¿El usuario actual es administrador? */
function es_admin(): bool
{
    return ($_SESSION['rol_id'] ?? 0) === ROL_ADMIN;
}

/** Página de inicio según el rol (ruta relativa a la raíz del proyecto). */
function ruta_inicio(): string
{
    return es_admin() ? 'admin/index.php' : 'dashboard.php';
}

/** Guarda los datos del usuario en la sesión después de autenticarse. */
function iniciar_sesion_usuario(int $id, string $nombre, string $correo, int $rolId): void
{
    session_regenerate_id(true); // nuevo id de sesión: evita fijación de sesión
    $_SESSION['usuario_id']     = $id;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_correo'] = $correo;
    $_SESSION['rol_id']         = $rolId;
}

/**
 * Mensajes "flash": se guardan en la sesión y se muestran una sola vez
 * en la siguiente página (header.php los imprime).
 *   flash('Texto', 'exito')  -> guarda un mensaje (tipos: exito, error, info)
 *   flash()                  -> obtiene y borra el mensaje pendiente
 */
function flash(?string $mensaje = null, string $tipo = 'info'): ?array
{
    if ($mensaje !== null) {
        $_SESSION['flash'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
        return null;
    }
    $pendiente = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $pendiente;
}

/** Redirige y termina el script. */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit();
}

/** Solo usuarios autenticados (cualquier rol). $base = '../' en subcarpetas. */
function requerir_login(string $base = ''): void
{
    if (!usuario_autenticado()) {
        flash('Inicia sesión para continuar.', 'error');
        redirigir($base . 'login.php');
    }
}

/** Solo administradores. */
function requerir_admin(string $base = ''): void
{
    requerir_login($base);
    if (!es_admin()) {
        flash('No tienes permiso para acceder a esa sección.', 'error');
        redirigir($base . 'dashboard.php');
    }
}
