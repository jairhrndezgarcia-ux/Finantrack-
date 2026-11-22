<?php
// registro.php - Alta de nuevos usuarios (Módulo 2)

session_start();
require_once 'config/conexion.php';

/**
 * Muestra un mensaje con alert() y redirige.
 * json_encode escapa comillas y etiquetas, así un nombre con caracteres
 * especiales no puede romper ni inyectar código en el <script> (XSS).
 */
function responder(string $mensaje, string $destino): void
{
    $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    echo '<script>alert(' . json_encode($mensaje, $flags) . ');'
       . 'window.location.href = ' . json_encode($destino, $flags) . ';</script>';
    exit();
}

// Si entran directo por la URL (GET), regresar al inicio
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit();
}

// 1. Capturar y limpiar los datos del formulario
$nombre    = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$correo    = strtolower(trim($_POST['correo'] ?? ''));
$divisa    = $_POST['divisa'] ?? '';
$password  = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar_password'] ?? '';

$volver = 'index.html#registro';

// 2. Validaciones del lado del servidor (la validación de JS se puede saltar)
if ($nombre === '' || $apellidos === '' || $correo === '' || $divisa === '' || $password === '') {
    responder('Todos los campos son obligatorios.', $volver);
}
if (mb_strlen($nombre) > 60 || mb_strlen($apellidos) > 80) {
    responder('El nombre o los apellidos son demasiado largos.', $volver);
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 120) {
    responder('El correo electrónico no es válido.', $volver);
}
if (strlen($password) < 6) {
    responder('La contraseña debe tener al menos 6 caracteres.', $volver);
}
if ($password !== $confirmar) {
    responder('Las contraseñas no coinciden.', $volver);
}

try {
    // 3. Obtener el id de la divisa elegida (solo divisas activas del catálogo)
    $stmt = mysqli_prepare($conexion, 'SELECT id FROM divisas WHERE codigo = ? AND activo = 1');
    mysqli_stmt_bind_param($stmt, 's', $divisa);
    mysqli_stmt_execute($stmt);
    $filaDivisa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$filaDivisa) {
        responder('La divisa seleccionada no es válida.', $volver);
    }
    $divisaId = (int) $filaDivisa['id'];

    // 4. Verificar que el correo no esté registrado (consulta preparada = sin inyección SQL)
    $stmt = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE correo = ?');
    mysqli_stmt_bind_param($stmt, 's', $correo);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        responder('El correo electrónico ya está registrado. Intenta con otro.', $volver);
    }

    // 5. Insertar el usuario con la contraseña cifrada (rol 2 = Usuario registrado)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO usuarios (nombre, apellidos, correo, password_hash, divisa_id, rol_id)
         VALUES (?, ?, ?, ?, ?, 2)'
    );
    mysqli_stmt_bind_param($stmt, 'ssssi', $nombre, $apellidos, $correo, $hash, $divisaId);
    mysqli_stmt_execute($stmt);

    // 6. Iniciar la sesión del nuevo usuario
    session_regenerate_id(true);
    $_SESSION['usuario_id']     = mysqli_insert_id($conexion);
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_correo'] = $correo;
    $_SESSION['rol_id']         = 2;

    responder("¡Registro exitoso! Bienvenido a FinanTrack, $nombre.", 'index.html');

} catch (mysqli_sql_exception $e) {
    // El detalle técnico va al log, no al navegador
    error_log('Error en registro: ' . $e->getMessage());
    responder('Ocurrió un error al registrar la cuenta. Intenta más tarde.', $volver);
}
