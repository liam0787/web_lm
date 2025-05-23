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

$mensaje = '';
$usuario = $_SESSION['usuario'];

// Contar artículos en el carrito
$carrito_count = 0;
$stmt = $conn->prepare("SELECT SUM(cantidad) FROM cesta WHERE usuario = ?");
$stmt->execute([$usuario]);
$carrito_count = (int) $stmt->fetchColumn();

// Actualizar cantidad de producto en la cesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $item_id = $_POST['item_id'];
    $nueva_cantidad = max(1, intval($_POST['cantidad']));
    $stmt = $conn->prepare("UPDATE cesta SET cantidad = ? WHERE id = ? AND usuario = ?");
    $stmt->execute([$nueva_cantidad, $item_id, $usuario]);
    header("Location: cart.php");
    exit();
}

// Procesar eliminación de producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $item_id = $_POST['item_id'];
    $stmt = $conn->prepare("DELETE FROM cesta WHERE id = ? AND usuario = ?");
    $stmt->execute([$item_id, $usuario]);
    header("Location: cart.php");
    exit();
}

// Procesar checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Obtener items del carrito antes de vaciarlo
    $stmt = $conn->prepare("SELECT c.*, p.nombre, p.precio FROM cesta c JOIN productos p ON c.referencia_producto = p.referencia WHERE c.usuario = ?");
    $stmt->execute([$usuario]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    foreach ($items as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }

    if (empty($items)) {
        header("Location: cart.php?compra=empty");
        exit();
    } else {
        try {
            $conn->beginTransaction();

            // Insertar en la tabla compras
            $fecha_compra = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO compras (usuario, fecha_compra, total) VALUES (?, ?, ?)");
            $stmt->execute([$usuario, $fecha_compra, $total]);
            $compra_id = $conn->lastInsertId();

            // Insertar detalles de la compra
            foreach ($items as $item) {
                $stmt = $conn->prepare("INSERT INTO detalles_compra (compra_id, referencia_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt->execute([$compra_id, $item['referencia_producto'], $item['cantidad'], $item['precio']]);
            }

            // Vaciar la cesta
            $stmt = $conn->prepare("DELETE FROM cesta WHERE usuario = ?");
            $stmt->execute([$usuario]);

            $conn->commit();
            header("Location: cart.php?compra=ok");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            header("Location: cart.php?compra=error");
            exit();
        }
    }
}

// Obtener items del carrito para mostrar (ahora también obtenemos la imagen)
$stmt = $conn->prepare("SELECT c.*, p.nombre, p.precio, p.imagen FROM cesta c JOIN productos p ON c.referencia_producto = p.referencia WHERE c.usuario = ?");
$stmt->execute([$usuario]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Mensaje tras compra
if (isset($_GET['compra'])) {
    if ($_GET['compra'] === 'ok') {
        $mensaje = "Compra realizada con éxito.";
    } elseif ($_GET['compra'] === 'error') {
        $mensaje = "Error al procesar la compra.";
    } elseif ($_GET['compra'] === 'empty') {
        $mensaje = "La cesta está vacía.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cesta de Compra</title>
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
        <h2 class="text-3xl font-bold mb-6">Cesta de Compra</h2>

        <?php if ($mensaje): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <p class="text-gray-600">Tu cesta está vacía.</p>
        <?php else: ?>
            <div class="bg-white p-4 rounded-lg shadow-md mb-6 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left p-2">Imagen</th>
                            <th class="text-left p-2">Producto</th>
                            <th class="text-left p-2">Precio</th>
                            <th class="text-left p-2">Cantidad</th>
                            <th class="text-left p-2">Subtotal</th>
                            <th class="text-left p-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="border-b">
                                <td class="p-2">
                                    <?php if ($item['imagen']): ?>
                                        <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="h-16 w-16 object-cover rounded">
                                    <?php endif; ?>
                                </td>
                                <td class="p-2"><?php echo htmlspecialchars($item['nombre']); ?></td>
                                <td class="p-2"><?php echo number_format($item['precio'], 2); ?> €</td>
                                <td class="p-2">
                                    <form method="POST" class="flex items-center gap-2">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" class="w-16 px-2 py-1 border rounded text-center" />
                                        <button type="submit" name="update_qty" class="bg-gray-800 text-white px-2 py-1 rounded hover:bg-gray-400" title="Actualizar cantidad">Actualizar</button>
                                    </form>
                                </td>
                                <td class="p-2"><?php echo number_format($item['precio'] * $item['cantidad'], 2); ?> €</td>
                                <td class="p-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-xl font-bold mt-4">Total: <?php echo number_format($total, 2); ?> €</p>
            </div>
            <div class="flex gap-2">
                <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-300">Seguir comprando</a>
                <form method="POST">
                    <button type="submit" name="checkout" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-400">
                        Realizar Compra (Prueba)
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </main>
    <!-- FOOTER -->
<footer class="bg-gray-800 text-white text-center p-4 mt-8 mt-auto">
    &copy; <?php echo date('Y'); ?> GavaStore. Todos los derechos reservados.
</footer>
</body>
</html>