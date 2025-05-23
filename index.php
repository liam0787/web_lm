<?php
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

// Contar artículos en el carrito
$carrito_count = 0;
if (isset($_SESSION['usuario'])) {
    $stmt = $conn->prepare("SELECT SUM(cantidad) FROM cesta WHERE usuario = ?");
    $stmt->execute([$_SESSION['usuario']]);
    $carrito_count = (int) $stmt->fetchColumn();
}

// Obtener categorías únicas
$stmt = $conn->query("SELECT * FROM categorias");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtrar productos por categoría si se seleccionó una
$selectedCategoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
if ($selectedCategoria) {
    $stmt = $conn->prepare("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE c.id = ?");
    $stmt->execute([$selectedCategoria]);
} else {
    $stmt = $conn->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id");
}
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener 3 productos para el carrusel (los primeros con imagen)
$stmt = $conn->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE imagen IS NOT NULL LIMIT 3");
$carouselProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener 4 productos aleatorios
$stmt = $conn->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id ORDER BY RAND() LIMIT 4");
$randomProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Añadir producto a la cesta
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $referencia = $_POST['referencia'];
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

        // ACTUALIZA EL CONTADOR DESPUÉS DE AÑADIR
        $stmt = $conn->prepare("SELECT SUM(cantidad) FROM cesta WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $carrito_count = (int) $stmt->fetchColumn();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GavaStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-100">
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

    <main class="container mx-auto p-4">
        <?php if (isset($mensaje)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Carrusel -->
        <div class="relative w-full h-64 mb-8 overflow-hidden rounded-lg shadow-lg">
            <div class="carousel w-full h-full">
                <?php foreach ($carouselProductos as $index => $producto): ?>
                    <div id="slide<?php echo $index + 1; ?>" class="carousel-item relative w-full h-full">
                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                            <div class="text-center text-white">
                                <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($producto['nombre']); ?></h2>
                                <!-- Descripción eliminada del carrusel -->
                                <p class="text-xl font-bold mt-2"><?php echo number_format($producto['precio'], 2); ?> €</p>
                            </div>
                        </div>
                        <div class="absolute flex justify-between transform -translate-y-1/2 left-5 right-5 top-1/2">
                            <a href="#slide<?php echo $index == 0 ? count($carouselProductos) : $index; ?>" class="btn btn-circle bg-white text-blue-600">❮</a>
                            <a href="#slide<?php echo $index == count($carouselProductos) - 1 ? 1 : $index + 2; ?>" class="btn btn-circle bg-white text-blue-600">❯</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Menú de categorías -->
<div class="mb-8">
    <h2 class="text-2xl font-bold mb-4">Categorías</h2>
    <div class="flex flex-wrap gap-4">
        <a href="index.php" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-400 <?php echo !$selectedCategoria ? 'bg-gray-400' : ''; ?>">Todas</a>
        <?php foreach ($categorias as $categoria): ?>
            <a href="index.php?categoria=<?php echo urlencode($categoria['id']); ?>" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-400 <?php echo $selectedCategoria == $categoria['id'] ? 'bg-gray-400' : ''; ?>">
                <?php echo htmlspecialchars($categoria['nombre']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Lista de productos -->
<h2 class="text-2xl font-bold mb-6">Productos <?php echo $selectedCategoria ? 'en ' . htmlspecialchars($conn->query("SELECT nombre FROM categorias WHERE id = " . $selectedCategoria)->fetchColumn()) : ''; ?></h2>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
    <?php foreach ($productos as $producto): ?>
        <div class="bg-white p-4 rounded-lg shadow-md">
            <?php if ($producto['imagen']): ?>
                <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="w-full h-48 object-cover mb-4 rounded">
            <?php endif; ?>
            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
            <p class="text-gray-600"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></p>
            <p class="text-lg font-bold text-blue-600"><?php echo number_format($producto['precio'], 2); ?> €</p>
            <div class="mt-4 space-x-2">
                <form method="POST" class="inline">
                    <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($producto['referencia']); ?>">
                    <button type="submit" name="add_to_cart" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-400">Añadir al carrito</button>
                </form>
                <a href="product.php?ref=<?php echo urlencode($producto['referencia']); ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-300">Más información</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Productos destacados aleatorios -->
<div class="mb-8 mt-12">
    <h2 class="text-2xl font-bold mb-4">Productos Destacados</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <?php foreach ($randomProductos as $producto): ?>
            <div class="bg-white p-4 rounded-lg shadow-md">
                <?php if ($producto['imagen']): ?>
                    <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="w-full h-48 object-cover mb-4 rounded">
                <?php endif; ?>
                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></p>
                <p class="text-lg font-bold text-blue-600"><?php echo number_format($producto['precio'], 2); ?> €</p>
                <div class="mt-4 space-x-2">
                    <form method="POST" class="inline">
                        <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($producto['referencia']); ?>">
                        <button type="submit" name="add_to_cart" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-400">Añadir al carrito</button>
                    </form>
                    <a href="product.php?ref=<?php echo urlencode($producto['referencia']); ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-300">Más información</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
    </main>
    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        &copy; <?php echo date('Y'); ?> GavaStore . Todos los derechos reservados.
    </footer>
</body>
</html>