<?php
session_start();
require_once 'conexion.php';
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("No se pudo conectar a la base de datos.");
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $usuario = trim($_POST['usuario']);
        $contrasena = trim($_POST['contrasena']);

        if (empty($usuario) || empty($contrasena)) {
            $mensaje = "Usuario y contraseña son obligatorios.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($contrasena, $user['contrasena'])) {
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['rol'] = $user['rol'];
                header("Location: index.php");
                exit();
            } else {
                $mensaje = "Usuario o contraseña incorrectos.";
            }
        }
    } elseif (isset($_POST['register'])) {
        $usuario = trim($_POST['usuario']);
        $contrasena = trim($_POST['contrasena']);
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $correo = trim($_POST['correo']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $genero = trim($_POST['genero']);
        $direccion_envio = trim($_POST['direccion_envio']);

        if (empty($usuario) || empty($contrasena) || empty($nombre) || empty($apellidos) || empty($correo) || empty($fecha_nacimiento) || empty($genero)) {
            $mensaje = "Todos los campos obligatorios son requeridos.";
        } else {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
                $stmt->execute([$usuario]);
                if ($stmt->rowCount() > 0) {
                    $mensaje = "El usuario ya existe.";
                    $conn->rollBack();
                } else {
                    $stmt = $conn->prepare("SELECT * FROM clientes WHERE correo = ?");
                    $stmt->execute([$correo]);
                    if ($stmt->rowCount() > 0) {
                        $mensaje = "El correo ya está registrado.";
                        $conn->rollBack();
                    } else {
                        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO usuarios (usuario, contrasena, rol) VALUES (?, ?, 'user')");
                        $stmt->execute([$usuario, $contrasena_hash]);

                        $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellidos, correo, fecha_nacimiento, genero, usuario, direccion_envio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nombre, $apellidos, $correo, $fecha_nacimiento, $genero, $usuario, $direccion_envio]);

                        $conn->commit();
                        $mensaje = "Usuario registrado con éxito. Por favor, inicia sesión.";
                    }
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                $mensaje = "Error al registrar el usuario: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión / Registro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <!-- Logo grande y centrado -->
        <div class="flex justify-center mb-6">
            <img src="logo2.png" alt="GavaStore" class="h-28 md:h-32 w-auto">
        </div>
        <div class="bg-white p-8 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Logate en GavaStore</h2>

            <?php if ($mensaje): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <h3 class="text-xl font-semibold mb-4 text-gray-700">Iniciar Sesión</h3>
            <form method="POST" class="mb-6">
                <div class="mb-4">
                    <label class="block text-gray-700">Usuario</label>
                    <input type="text" name="usuario" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Contraseña</label>
                    <input type="password" name="contrasena" class="w-full p-2 border rounded" required>
                </div>
                <button type="submit" name="login" class="w-full bg-gray-800 text-white p-2 rounded hover:bg-gray-900 transition">
                    Iniciar Sesión
                </button>
            </form>

            <p class="text-center mb-4 text-gray-600">¿No tienes cuenta? <a href="register.php" class="text-gray-800 font-semibold hover:underline">Regístrate aquí</a></p>
        </div>
    </div>
</body>
</html>