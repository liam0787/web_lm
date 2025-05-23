<?php
session_start();
require_once 'conexion.php';
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("No se pudo conectar a la base de datos.");
}

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];

$mensaje = '';
$uploadDir = 'images/';

// Obtener categorías
$stmt = $conn->query("SELECT * FROM categorias");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determinar la sección activa
$section = isset($_GET['section']) ? $_GET['section'] : 'productos';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product']) && $section === 'productos') {
        $referencia = $_POST['referencia'];
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $descripcion = $_POST['descripcion'];
        $categoria_id = $_POST['categoria_id'];
        $imagen = null;

        if (empty($referencia) || empty($nombre) || empty($precio) || empty($categoria_id)) {
            $mensaje = "Todos los campos obligatorios (Referencia, Nombre, Precio, Categoría) son requeridos.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM productos WHERE referencia = ?");
            $stmt->execute([$referencia]);
            if ($stmt->rowCount() > 0) {
                $mensaje = "La referencia del producto ya existe.";
            } else {
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $imagenName = basename($_FILES['imagen']['name']);
                    $imagenPath = $uploadDir . $imagenName;
                    $imagenRelativePath = 'images/' . $imagenName;
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = mime_content_type($_FILES['imagen']['tmp_name']);
                    if (!in_array($fileType, $allowedTypes)) {
                        $mensaje = "Solo se permiten imágenes (JPEG, PNG, GIF).";
                    } else {
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagenPath)) {
                            $imagen = $imagenRelativePath;
                            $stmt = $conn->prepare("INSERT INTO productos (referencia, nombre, precio, imagen, descripcion, categoria_id) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$referencia, $nombre, $precio, $imagen, $descripcion, $categoria_id]);
                            $mensaje = "Producto añadido con éxito.";
                        } else {
                            $mensaje = "Error al subir la imagen. Verifica los permisos de la carpeta 'images'.";
                        }
                    }
                } else {
                    $stmt = $conn->prepare("INSERT INTO productos (referencia, nombre, precio, descripcion, categoria_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$referencia, $nombre, $precio, $descripcion, $categoria_id]);
                    $mensaje = "Producto añadido con éxito (sin imagen).";
                }
            }
        }
    } elseif (isset($_POST['delete_product']) && $section === 'productos') {
        $referencia = $_POST['referencia'];
        // Verificar si el producto está en detalles_compra
        $stmt = $conn->prepare("SELECT COUNT(*) FROM detalles_compra WHERE referencia_producto = ?");
        $stmt->execute([$referencia]);
        if ($stmt->fetchColumn() > 0) {
            $mensaje = "No se puede eliminar el producto porque tiene compras asociadas.";
        } else {
            // Verificar si el producto está en alguna cesta
            $stmt = $conn->prepare("SELECT COUNT(*) FROM cesta WHERE referencia_producto = ?");
            $stmt->execute([$referencia]);
            if ($stmt->fetchColumn() > 0) {
                $mensaje = "No se puede eliminar el producto porque está en alguna cesta de usuario.";
            } else {
                $stmt = $conn->prepare("DELETE FROM productos WHERE referencia = ?");
                $stmt->execute([$referencia]);
                $mensaje = "Producto eliminado con éxito.";
            }
        }
    } elseif (isset($_POST['update_product']) && $section === 'productos') {
        $referencia = $_POST['referencia'];
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $descripcion = $_POST['descripcion'];
        $categoria_id = $_POST['categoria_id'];
        $imagen = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $imagenName = basename($_FILES['imagen']['name']);
            $imagenPath = $uploadDir . $imagenName;
            $imagenRelativePath = 'images/' . $imagenName;
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($_FILES['imagen']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $mensaje = "Solo se permiten imágenes (JPEG, PNG, GIF).";
            } else {
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagenPath)) {
                    $imagen = $imagenRelativePath;
                    $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, imagen = ?, descripcion = ?, categoria_id = ? WHERE referencia = ?");
                    $stmt->execute([$nombre, $precio, $imagen, $descripcion, $categoria_id, $referencia]);
                    $mensaje = "Producto actualizado con éxito.";
                } else {
                    $mensaje = "Error al actualizar la imagen. Verifica los permisos de la carpeta 'images'.";
                }
            }
        } else {
            $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, descripcion = ?, categoria_id = ? WHERE referencia = ?");
            $stmt->execute([$nombre, $precio, $descripcion, $categoria_id, $referencia]);
            $mensaje = "Producto actualizado con éxito (sin imagen).";
        }
    } elseif (isset($_POST['add_category']) && $section === 'categorias') {
        $nombre = trim($_POST['category_name']);
        if (empty($nombre)) {
            $mensaje = "El nombre de la categoría no puede estar vacío.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            $mensaje = "Categoría añadida con éxito.";
        }
    } elseif (isset($_POST['update_category']) && $section === 'categorias') {
        $id = $_POST['category_id'];
        $nombre = trim($_POST['category_name']);
        if (empty($nombre)) {
            $mensaje = "El nombre de la categoría no puede estar vacío.";
        } else {
            $stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $id]);
            $mensaje = "Categoría actualizada con éxito.";
        }
    } elseif (isset($_POST['delete_category']) && $section === 'categorias') {
        $id = $_POST['category_id'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $mensaje = "No se puede eliminar la categoría porque tiene productos asociados.";
        } else {
            $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Categoría eliminada con éxito.";
        }
    } elseif (isset($_POST['delete_user']) && $section === 'usuarios') {
        $usuario = $_POST['usuario'];
        if ($usuario !== 'admin') {
            // Verificar si el usuario tiene compras asociadas
            $stmt = $conn->prepare("SELECT COUNT(*) FROM compras WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetchColumn() > 0) {
                $mensaje = "No se puede eliminar el usuario porque tiene compras asociadas.";
            } else {
                // Primero borra de clientes, luego de usuarios
                $stmt = $conn->prepare("DELETE FROM clientes WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $mensaje = "Usuario eliminado con éxito.";
            }
        } else {
            $mensaje = "No puedes eliminar la cuenta de admin.";
        }
    } elseif (isset($_POST['update_user']) && $section === 'usuarios') {
        $usuario = $_POST['usuario'];
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
                $stmt = $conn->prepare("SELECT * FROM clientes WHERE usuario = ?");
                $stmt->execute([$usuario]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, apellidos = ?, correo = ?, fecha_nacimiento = ?, genero = ?, direccion_envio = ? WHERE usuario = ?");
                    $stmt->execute([$nombre, $apellidos, $correo, $fecha_nacimiento, $genero, $direccion_envio, $usuario]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellidos, correo, fecha_nacimiento, genero, usuario, direccion_envio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $apellidos, $correo, $fecha_nacimiento, $genero, $usuario, $direccion_envio]);
                }
                $mensaje = "Usuario actualizado con éxito.";
            }
        }
    } elseif (isset($_POST['download_xml'])) {
        $stmt = $conn->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $xml = new SimpleXMLElement('<catalogo/>');
        foreach ($productos as $producto) {
            $item = $xml->addChild('producto');
            $item->addChild('referencia', htmlspecialchars($producto['referencia']));
            $item->addChild('nombre', htmlspecialchars($producto['nombre']));
            $item->addChild('precio', number_format($producto['precio'], 2));
            if ($producto['imagen']) $item->addChild('imagen', htmlspecialchars($producto['imagen']));
            if ($producto['descripcion']) $item->addChild('descripcion', htmlspecialchars($producto['descripcion']));
            $item->addChild('categoria', htmlspecialchars($producto['categoria']));
        }

        $xml->asXML('catalogo.xml');
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="catalogo.xml"');
        echo $xml->asXML();
        exit();
    }
}

// Obtener datos según la sección
if ($section === 'productos') {
    $stmt = $conn->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $productos = [];
}

if ($section === 'categorias') {
    $stmt = $conn->query("SELECT * FROM categorias");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($section === 'usuarios') {
    $stmt = $conn->query("SELECT u.usuario, c.nombre, c.apellidos, c.correo, c.fecha_nacimiento, c.genero, c.direccion_envio FROM usuarios u LEFT JOIN clientes c ON u.usuario = c.usuario");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $usuarios = [];
}

if ($section === 'compras') {
    $stmt = $conn->query("SELECT c.id, c.usuario, c.fecha_compra, c.total, u.nombre, u.apellidos FROM compras c JOIN clientes u ON c.usuario = u.usuario");
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $compras = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gray-100 flex">
    <!-- Menú lateral retráctil -->
    <div id="sidebar" class="bg-gray-800 text-white w-64 min-h-screen p-4 transition-all duration-300">
        <div class="flex justify-between items-center mb-6">
            <a href="index.php">
                <img src="logo.png" alt="GavaStore" class="h-20 w-auto">
            </a>
            <button id="toggleSidebar" class="text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        <nav>
            <ul>
                <li class="mb-2">
                    <a href="?section=productos" class="block px-4 py-2 rounded hover:bg-gray-400 <?php echo $section === 'productos' ? 'bg-gray-400' : ''; ?>">Productos</a>
                </li>
                <li class="mb-2">
                    <a href="?section=categorias" class="block px-4 py-2 rounded hover:bg-gray-400 <?php echo $section === 'categorias' ? 'bg-gray-400' : ''; ?>">Categorías</a>
                </li>
                <li class="mb-2">
                    <a href="?section=usuarios" class="block px-4 py-2 rounded hover:bg-gray-400 <?php echo $section === 'usuarios' ? 'bg-gray-400' : ''; ?>">Usuarios</a>
                </li>
                <li class="mb-2">
                    <a href="?section=compras" class="block px-4 py-2 rounded hover:bg-gray-400 <?php echo $section === 'compras' ? 'bg-gray-400' : ''; ?>">Compras</a>
                </li>
                <li class="mb-2">
                    <form method="POST" class="inline">
                        <button type="submit" name="download_xml" class="block w-full text-left px-4 py-2 rounded hover:bg-gray-400">Descargar Catálogo XML</button>
                    </form>
                </li>
                <li class="mb-2">
                    <a href="index.php" class="block px-4 py-2 rounded hover:bg-gray-400">Volver a Tienda</a>
                </li>
                <li class="mb-2">
                    <a href="logout.php" class="block px-4 py-2 rounded hover:bg-gray-400">Cerrar Sesión</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Contenido principal -->
    <div class="flex-1 p-4">
        <header class="bg-gray-800 text-white p-4 mb-4 rounded-lg flex items-center gap-4">
            <h1 class="text-2xl font-bold">Panel de Administración</h1>
        </header>

        <main>
            <?php if ($mensaje): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Sección Productos -->
            <?php if ($section === 'productos'): ?>
                <h2 class="text-3xl font-bold mb-6">Gestión de Productos</h2>
                <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <h3 class="text-xl font-semibold mb-4">Añadir Producto</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700">Referencia</label>
                        <input type="text" name="referencia" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Nombre</label>
                        <input type="text" name="nombre" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Precio</label>
                        <input type="number" step="0.01" name="precio" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Categoría</label>
                        <select name="categoria_id" class="w-full p-2 border rounded" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['id']); ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Imagen</label>
                        <input type="file" name="imagen" class="w-full p-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Descripción</label>
                        <textarea name="descripcion" class="w-full p-2 border rounded" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_product" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Añadir Producto
                    </button>
                </form>

                <h3 class="text-xl font-semibold mb-4">Productos</h3>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Referencia</th>
                                <th class="text-left p-2">Nombre</th>
                                <th class="text-left p-2">Precio</th>
                                <th class="text-left p-2">Categoría</th>
                                <th class="text-left p-2">Imagen</th>
                                <th class="text-left p-2">Descripción</th>
                                <th class="text-left p-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo htmlspecialchars($producto['referencia']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td class="p-2"><?php echo number_format($producto['precio'], 2); ?> €</td>
                                    <td class="p-2"><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($producto['imagen'] ?? 'Sin imagen'); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($producto['descripcion'] ?? 'Sin descripción'); ?></td>
                                    <td class="p-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($producto['referencia']); ?>">
                                            <button type="submit" name="delete_product" class="text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                        <button onclick="document.getElementById('edit-<?php echo htmlspecialchars($producto['referencia']); ?>').classList.remove('hidden')" class="text-blue-600 hover:underline">Editar</button>
                                        <div id="edit-<?php echo htmlspecialchars($producto['referencia']); ?>" class="hidden mt-2">
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($producto['referencia']); ?>">
                                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" class="p-1 border rounded" required>
                                                <input type="number" step="0.01" name="precio" value="<?php echo $producto['precio']; ?>" class="p-1 border rounded" required>
                                                <select name="categoria_id" class="p-1 border rounded" required>
                                                    <?php foreach ($categorias as $cat): ?>
                                                        <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php if ($producto['categoria_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="file" name="imagen" class="p-1 border rounded">
                                                <textarea name="descripcion" class="w-full p-1 border rounded" rows="3"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                                                <button type="submit" name="update_product" class="bg-blue-600 text-white px-2 py-1 rounded">Actualizar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Sección Categorías -->
            <?php if ($section === 'categorias'): ?>
                <h2 class="text-3xl font-bold mb-6">Gestión de Categorías</h2>
                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <form method="POST" class="mb-4">
                        <input type="text" name="category_name" class="w-full p-2 border rounded mb-2" placeholder="Nueva categoría" required>
                        <button type="submit" name="add_category" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Añadir Categoría</button>
                    </form>
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">ID</th>
                                <th class="text-left p-2">Nombre</th>
                                <th class="text-left p-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo htmlspecialchars($cat['id']); ?></td>
                                    <td class="p-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($cat['id']); ?>">
                                            <input type="text" name="category_name" value="<?php echo htmlspecialchars($cat['nombre']); ?>" class="p-1 border rounded" required>
                                            <button type="submit" name="update_category" class="bg-blue-600 text-white px-2 py-1 rounded ml-2">Actualizar</button>
                                        </form>
                                    </td>
                                    <td class="p-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($cat['id']); ?>">
                                            <button type="submit" name="delete_category" class="text-red-600 hover:underline" onclick="return confirm('¿Estás seguro de eliminar esta categoría?');">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Sección Usuarios -->
            <?php if ($section === 'usuarios'): ?>
                <h2 class="text-3xl font-bold mb-6">Gestión de Usuarios</h2>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Usuario</th>
                                <th class="text-left p-2">Nombre</th>
                                <th class="text-left p-2">Apellidos</th>
                                <th class="text-left p-2">Correo</th>
                                <th class="text-left p-2">Fecha de Nacimiento</th>
                                <th class="text-left p-2">Género</th>
                                <th class="text-left p-2">Dirección de Envío</th>
                                <th class="text-left p-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['apellidos'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['correo'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['genero'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($usuario['direccion_envio'] ?? ''); ?></td>
                                    <td class="p-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>">
                                            <button type="submit" name="delete_user" class="text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                        <button onclick="document.getElementById('edit-user-<?php echo htmlspecialchars($usuario['usuario']); ?>').classList.remove('hidden')" class="text-blue-600 hover:underline">Editar</button>
                                        <div id="edit-user-<?php echo htmlspecialchars($usuario['usuario']); ?>" class="hidden mt-2">
                                            <form method="POST">
                                                <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>">
                                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" placeholder="Nombre" class="p-1 border rounded" required>
                                                <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos'] ?? ''); ?>" placeholder="Apellidos" class="p-1 border rounded" required>
                                                <input type="email" name="correo" value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" placeholder="Correo" class="p-1 border rounded" required>
                                                <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? ''); ?>" class="p-1 border rounded" required>
                                                <select name="genero" class="p-1 border rounded" required>
                                                    <option value="M" <?php if ($usuario['genero'] == 'M') echo 'selected'; ?>>Masculino</option>
                                                    <option value="F" <?php if ($usuario['genero'] == 'F') echo 'selected'; ?>>Femenino</option>
                                                    <option value="Otro" <?php if ($usuario['genero'] == 'Otro') echo 'selected'; ?>>Otro</option>
                                                </select>
                                                <textarea name="direccion_envio" class="w-full p-1 border rounded" placeholder="Dirección de envío"><?php echo htmlspecialchars($usuario['direccion_envio'] ?? ''); ?></textarea>
                                                <button type="submit" name="update_user" class="bg-blue-600 text-white px-2 py-1 rounded">Actualizar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Sección Compras -->
            <?php if ($section === 'compras'): ?>
                <h2 class="text-3xl font-bold mb-6">Gestión de Compras</h2>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">ID Compra</th>
                                <th class="text-left p-2">Usuario</th>
                                <th class="text-left p-2">Nombre</th>
                                <th class="text-left p-2">Fecha</th>
                                <th class="text-left p-2">Total</th>
                                <th class="text-left p-2">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compras as $compra): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo htmlspecialchars($compra['id']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($compra['usuario']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($compra['nombre'] . ' ' . $compra['apellidos']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($compra['fecha_compra']); ?></td>
                                    <td class="p-2"><?php echo number_format($compra['total'], 2); ?> €</td>
                                    <td class="p-2">
                                        <button onclick="document.getElementById('detalles-<?php echo $compra['id']; ?>').classList.toggle('hidden')" class="text-blue-600 hover:underline">Ver Detalles</button>
                                        <div id="detalles-<?php echo $compra['id']; ?>" class="hidden mt-2">
                                            <?php
                                            $stmt = $conn->prepare("SELECT dc.*, p.nombre FROM detalles_compra dc JOIN productos p ON dc.referencia_producto = p.referencia WHERE dc.compra_id = ?");
                                            $stmt->execute([$compra['id']]);
                                            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <table class="w-full mt-2 border">
                                                <thead>
                                                    <tr class="border-b">
                                                        <th class="text-left p-2">Producto</th>
                                                        <th class="text-left p-2">Cantidad</th>
                                                        <th class="text-left p-2">Precio Unitario</th>
                                                        <th class="text-left p-2">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($detalles as $detalle): ?>
                                                        <tr class="border-b">
                                                            <td class="p-2"><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                                                            <td class="p-2"><?php echo $detalle['cantidad']; ?></td>
                                                            <td class="p-2"><?php echo number_format($detalle['precio_unitario'], 2); ?> €</td>
                                                            <td class="p-2"><?php echo number_format($detalle['precio_unitario'] * $detalle['cantidad'], 2); ?> €</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- JavaScript para el menú retráctil -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');

        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-16');
            sidebar.classList.toggle('p-4');
            sidebar.classList.toggle('p-2');
            // Mostrar solo los iconos/texto principales al plegar
            const navLinks = sidebar.querySelectorAll('nav ul li');
            navLinks.forEach(li => {
                li.classList.toggle('hidden');
            });
            // Siempre mostrar el logo y el botón de menú
            sidebar.querySelector('div.flex').classList.toggle('justify-center');
        });
    </script>
</body>
</html>