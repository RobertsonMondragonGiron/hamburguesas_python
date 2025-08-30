<?php
// ===== ARCHIVO: productos.php (FALTABA) =====
?>
<!--- Separador para productos.php --->
<?php 
include("config/db.php");
include("header.php");
?>

<h2>📦 Productos</h2>

<a href="nuevo_producto.php" class="btn btn-success mb-3">➕ Nuevo Producto</a>

<table class="table table-striped table-hover bg-white shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Stock</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT * FROM productos ORDER BY nombre";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
            $stock_class = $row['stock'] <= 10 ? 'text-danger fw-bold' : '';
            echo "<tr>
                    <td>{$row['id_producto']}</td>
                    <td>{$row['nombre']}</td>
                    <td class='$stock_class'>{$row['stock']}</td>
                    <td>
                        <a href='editar_producto.php?id={$row['id_producto']}' class='btn btn-sm btn-primary'>✏️ Editar</a>
                        <a href='productos.php?eliminar={$row['id_producto']}' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Eliminar producto?')\">🗑️ Eliminar</a>
                    </td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<?php
// Eliminar producto
if(isset($_GET['eliminar'])){
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM productos WHERE id_producto = $id");
    echo "<script>window.location='productos.php';</script>";
}
?>

<?php include("footer.php"); ?>

<?php
// ===== ARCHIVO: nuevo_producto.php (FALTABA) =====
?>
<!--- Separador para nuevo_producto.php --->
<?php 
include("config/db.php");
include("header.php");
?>

<h2>➕ Nuevo Producto</h2>

<?php
if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $stock = (int)$_POST['stock'];
    
    $stmt = $conn->prepare("INSERT INTO productos (nombre, stock) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre, $stock);
    $stmt->execute();
    echo "<div class='alert alert-success'>✅ Producto registrado.</div>";
}
?>

<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label>Nombre del Producto</label>
        <input type="text" name="nombre" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Stock Inicial</label>
        <input type="number" name="stock" class="form-control" min="0" required>
    </div>
    <button name="guardar" class="btn btn-success">Guardar</button>
    <a href="productos.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include("footer.php"); ?>