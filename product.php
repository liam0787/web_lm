<?php
// tienda_web/index.php     
session_start();
require_once 'conexion.php';
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("No se pudo conectar a la base de datos.");
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$referencia = isset($_GET['ref']) ? $_GET['ref'] : '';
if (empty($referencia)) {
    die("Producto no encontrado.");
}

$stmt = $conn->prepare("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE referencia = ?");
$stmt->execute([$referencia]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    die("Producto no encontrado.");
}

// Añadir producto a la cesta
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $usuario = $_SESSION['usuario'];
    $cantidad = 1;

    $stmt = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    if ($stmt->rowCount() === 0) {
        $mensaje = "Error: El usuario no está registrado en el sistema.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM cesta WHERE usuario = ? AND referencia_producto = ?");
        $stmt->execute([$usuario, $referencia]);
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->prepare("UPDATE cesta SET cantidad = cantidad + 1 WHERE usuario = ? AND referencia_producto = ?");
            $stmt->execute([$usuario, $referencia]);
        } else {
            $stmt = $conn->prepare("INSERT INTO cesta (usuario, referencia_producto, cantidad) VALUES (?, ?, ?)");
            $stmt->execute([$usuario, $referencia, $cantidad]);
        }
        $mensaje = "Producto añadido a la cesta.";
    }
}

// Recuento del carrito
$carrito_count = 0;
if (isset($_SESSION['usuario'])) {
    $stmt = $conn->prepare("SELECT SUM(cantidad) FROM cesta WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $carrito_count = (int) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Tienda Virtual</title>
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
        <?php if ($mensaje): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row gap-8">
            <!-- Imagen grande -->
            <?php if ($producto['imagen']): ?>
                <div class="w-full md:w-1/2">
                    <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="w-full h-auto rounded-lg shadow-md">
                </div>
            <?php endif; ?>

            <!-- Detalles del producto -->
            <div class="w-full md:w-1/2">
                <h2 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($producto['nombre']); ?></h2>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></p>
                <p class="text-2xl font-bold text-blue-600 mb-4"><?php echo number_format($producto['precio'], 2); ?> €</p>
                <p class="text-lg mb-2"><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['categoria']); ?></p>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($producto['referencia']); ?>">
                    <button type="submit" name="add_to_cart" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-400">Añadir al carrito</button>
                </form>
                <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Volver a la tienda</a>
            </div>
        </div>
    </main>
    <!-- FOOTER -->
    <footer class="bg-gray-800 text-white text-center p-4 mt-8 mt-auto">
        &copy; <?php echo date('Y'); ?> GavaStore. Todos los derechos reservados.
    </footer>
</body>
</html>