<?php
session_start();
require_once 'conexion.php';
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("No se pudo conectar a la base de datos.");
}

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$mensaje = '';

// Contar artículos en el carrito
$carrito_count = 0;
$stmt = $conn->prepare("SELECT SUM(cantidad) FROM cesta WHERE usuario = ?");
$stmt->execute([$usuario]);
$carrito_count = (int) $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT c.nombre, c.apellidos, c.correo, c.fecha_nacimiento, c.genero, c.direccion_envio FROM clientes c WHERE c.usuario = ?");
$stmt->execute([$usuario]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    $mensaje = "No se encontraron datos para este usuario.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $correo = $_POST['correo'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['genero'];
    $direccion_envio = $_POST['direccion_envio'];

    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($fecha_nacimiento) || empty($genero)) {
        $mensaje = "Todos los campos obligatorios son requeridos.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE correo = ? AND usuario != ?");
        $stmt->execute([$correo, $usuario]);
        if ($stmt->rowCount() > 0) {
            $mensaje = "El correo ya está registrado por otro usuario.";
        } else {
            $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, apellidos = ?, correo = ?, fecha_nacimiento = ?, genero = ?, direccion_envio = ? WHERE usuario = ?");
            $stmt->execute([$nombre, $apellidos, $correo, $fecha_nacimiento, $genero, $direccion_envio, $usuario]);
            $mensaje = "Datos actualizados con éxito.";
            $stmt = $conn->prepare("SELECT c.nombre, c.apellidos, c.correo, c.fecha_nacimiento, c.genero, c.direccion_envio FROM clientes c WHERE c.usuario = ?");
            $stmt->execute([$usuario]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- HEADER -->
<header class="bg-gray-800 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
        <a href="index.php" class="flex items-center gap-3">
            <img src="logo.png" alt="GavaStore" class="h-24 w-auto">
            <span class="text-3xl font-bold tracking-wide hidden md:inline">La tienda de las Gavetas</span>
        </a>
        <!-- Botón hamburguesa para móvil -->
        <button id="menu-toggle" class="md:hidden focus:outline-none">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <!-- Menú de navegación -->
        <nav id="main-menu" class="flex flex-col md:flex-row md:items-center absolute md:static top-20 left-0 w-full md:w-auto bg-gray-800 md:bg-transparent z-50 md:z-auto hidden md:flex">
            <a href="index.php" class="block px-4 py-2 md:py-0 md:mr-4 hover:bg-gray-700 rounded">Tienda</a>
            <a href="profile.php" class="block px-4 py-2 md:py-0 md:mr-4 hover:bg-gray-700 rounded">Mi Perfil</a>
            <a href="cart.php" class="block px-4 py-2 md:py-0 md:mr-4 hover:bg-gray-700 rounded">
                Cesta
                <?php if (isset($carrito_count) && $carrito_count > 0): ?>
                    <span class="inline-block bg-white text-blue-600 font-bold rounded-full px-2 ml-1 text-sm align-middle"><?php echo $carrito_count; ?></span>
                <?php endif; ?>
            </a>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <a href="admin.php" class="block px-4 py-2 md:py-0 md:mr-4 hover:bg-gray-700 rounded">Panel de Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="block px-4 py-2 md:py-0 hover:bg-gray-700 rounded">Cerrar Sesión</a>
        </nav>
    </div>
</header>
<script>
    // Script para mostrar/ocultar el menú en móvil
    const menuToggle = document.getElementById('menu-toggle');
    const mainMenu = document.getElementById('main-menu');
    menuToggle.addEventListener('click', () => {
        mainMenu.classList.toggle('hidden');
    });
</script>

    <main class="container mx-auto p-4 flex-1">
        <h2 class="text-3xl font-bold mb-6">Mi Perfil</h2>

        <?php if ($mensaje): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($datos): ?>
            <form method="POST" class="bg-white p-4 rounded-lg shadow-md">
                <div class="mb-4">
                    <label class="block text-gray-700">Nombre</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($datos['nombre']); ?>" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Apellidos</label>
                    <input type="text" name="apellidos" value="<?php echo htmlspecialchars($datos['apellidos']); ?>" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Correo</label>
                    <input type="email" name="correo" value="<?php echo htmlspecialchars($datos['correo']); ?>" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($datos['fecha_nacimiento']); ?>" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Género</label>
                    <select name="genero" class="w-full p-2 border rounded" required>
                        <option value="M" <?php if ($datos['genero'] == 'M') echo 'selected'; ?>>Masculino</option>
                        <option value="F" <?php if ($datos['genero'] == 'F') echo 'selected'; ?>>Femenino</option>
                        <option value="Otro" <?php if ($datos['genero'] == 'Otro') echo 'selected'; ?>>Otro</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Dirección de Envío</label>
                    <textarea name="direccion_envio" class="w-full p-2 border rounded" placeholder="Dirección de envío"><?php echo htmlspecialchars($datos['direccion_envio'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-400">
                    Actualizar Datos
                </button>
            </form>
        <?php else: ?>
            <p class="text-gray-600">No se encontraron datos para este usuario.</p>
        <?php endif; ?>
    </main>
    <!-- FOOTER -->
<footer class="bg-gray-800 text-white text-center p-4 mt-8 mt-auto">
    &copy; <?php echo date('Y'); ?> GavaStore. Todos los derechos reservados.
</footer>
</body>
</html>