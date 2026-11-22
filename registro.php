<?php
// 1. Iniciar el manejo de sesiones 
session_start();

// 2. Incluir el archivo de conexión a MySQL 
require_once 'config/conexion.php';

// 3. Verificar que los datos hayan sido enviados por el método POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Capturar la información enviada por el formulario HTML
    $nombre    = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $correo    = $_POST['correo'];
    $divisa    = $_POST['divisa'];
    $password  = $_POST['password'];
    $confirmar = $_POST['confirmar_password'];

    // Validar en el servidor que las contraseñas coincidan
    if ($password !== $confirmar) {
        echo "<script>
                alert('Error: Las contraseñas no coinciden.');
                window.location.href = 'index.html#registro';
              </script>";
        exit();
    }

    // 4. Verificar si el correo ya existe en la base de datos 
    $consulta_existe = "SELECT COUNT(*) as total FROM usuarios WHERE correo = '$correo'";
    $query_existe    = mysqli_query($conexion, $consulta_existe);
    $resultado       = mysqli_fetch_array($query_existe);

    if ($resultado['total'] > 0) {
        echo "<script>
                alert('El correo electrónico ya está registrado. Intenta con otro.');
                window.location.href = 'index.html#registro';
              </script>";
        exit();
    }

    // 5. Preparar la sentencia de inserción 
    // El rol_id = 2 corresponde al 'Cliente / Usuario Registrado' de la propuesta
    $insert = "INSERT INTO usuarios (nombre, apellidos, correo, password, divisa_base, rol_id) 
               VALUES ('$nombre', '$apellidos', '$correo', '$password', '$divisa', 2)";

    // 6. Ejecutar la inserción dentro de un bloque try & catch
    try {
        $query_insert = mysqli_query($conexion, $insert);

        if ($query_insert) {
            // Guardar datos en la sesión activa 
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_correo'] = $correo;
            $_SESSION['rol_id']         = 2; // Cliente

            echo "<script>
                    alert('¡Registro exitoso! Bienvenido a FinanTrack, $nombre.');
                    window.location.href = 'index.html';
                  </script>";
        } else {
            echo "<script>
                    alert('Ocurrió un error al registrar la cuenta.');
                    window.location.href = 'index.html#registro';
                  </script>";
        }
    } catch (Exception $e) {
        // Capturar error si la inserción falla 
        echo "<script>
                alert('Error en el servidor: " . $e->getMessage() . "');
                window.location.href = 'index.html#registro';
              </script>";
    }

} else {
    // Si intentan entrar directamente a registro.php por la URL, redirigir al inicio
    header('Location: index.html');
    exit();
}
?>