<?php
// registro.php - Alta de nuevos usuarios (Módulo 2)
require_once 'includes/auth.php';
require_once 'config/conexion.php';

$volver = 'index.php#registro';

// Muestra el error en la página de registro y termina
function error_registro(string $mensaje, string $volver): void
{
    flash($mensaje, 'error');
    redirigir($volver);
}

// Si entran directo por la URL (GET) o ya tienen sesión, regresar
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}
if (usuario_autenticado()) {
    redirigir(ruta_inicio());
}

// 1. Capturar y limpiar los datos del formulario
$nombre    = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$correo    = strtolower(trim($_POST['correo'] ?? ''));
$divisa    = $_POST['divisa'] ?? '';
$password  = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar_password'] ?? '';

// 2. Validaciones del lado del servidor (la validación de JS se puede saltar)
if ($nombre === '' || $apellidos === '' || $correo === '' || $divisa === '' || $password === '') {
    error_registro('Todos los campos son obligatorios.', $volver);
}
if (mb_strlen($nombre) > 60 || mb_strlen($apellidos) > 80) {
    error_registro('El nombre o los apellidos son demasiado largos.', $volver);
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 120) {
    error_registro('El correo electrónico no es válido.', $volver);
}
if (strlen($password) < 6) {
    error_registro('La contraseña debe tener al menos 6 caracteres.', $volver);
}
if ($password !== $confirmar) {
    error_registro('Las contraseñas no coinciden.', $volver);
}

try {
    // 3. Obtener el id de la divisa elegida (solo divisas activas del catálogo)
    $stmt = mysqli_prepare($conexion, 'SELECT id FROM divisas WHERE codigo = ? AND activo = 1');
    mysqli_stmt_bind_param($stmt, 's', $divisa);
    mysqli_stmt_execute($stmt);
    $filaDivisa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$filaDivisa) {
        error_registro('La divisa seleccionada no es válida.', $volver);
    }
    $divisaId = (int) $filaDivisa['id'];

    // 4. Verificar que el correo no esté registrado (consulta preparada = sin inyección SQL)
    $stmt = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE correo = ?');
    mysqli_stmt_bind_param($stmt, 's', $correo);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        error_registro('El correo electrónico ya está registrado. Intenta con otro.', $volver);
    }

    // 5. Insertar el usuario con la contraseña cifrada (rol 2 = Usuario registrado)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO usuarios (nombre, apellidos, correo, password_hash, divisa_id, rol_id, ultimo_acceso)
         VALUES (?, ?, ?, ?, ?, ' . ROL_USUARIO . ', NOW())'
    );
    mysqli_stmt_bind_param($stmt, 'ssssi', $nombre, $apellidos, $correo, $hash, $divisaId);
    mysqli_stmt_execute($stmt);

    // 6. Iniciar la sesión del nuevo usuario y llevarlo a su panel
    iniciar_sesion_usuario(mysqli_insert_id($conexion), $nombre, $correo, ROL_USUARIO);
    flash("¡Registro exitoso! Bienvenido a FinanTrack, $nombre.", 'exito');
    redirigir('dashboard.php');

} catch (mysqli_sql_exception $e) {
    // El detalle técnico va al log, no al navegador
    error_log('Error en registro: ' . $e->getMessage());
    error_registro('Ocurrió un error al registrar la cuenta. Intenta más tarde.', $volver);
}
