<?php

include("config/db.php");
include("header.php");

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM productos WHERE id_producto = $id");
$producto = $result->fetch_assoc();

if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $stock = (int)$_POST['stock'];
    
    $stmt = $conn->prepare("UPDATE productos SET nombre = ?, stock = ? WHERE id_producto = ?");
    $stmt->bind_param("sii", $nombre, $stock, $id);
    $stmt->execute();
    echo "<div class='alert alert-success'>✅ Producto actualizado.</div>";
    $producto['nombre'] = $nombre;
    $producto['stock'] = $stock;
}
?>

<h2>✏️ Editar Producto</h2>

<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label>Nombre del Producto</label>
        <input type="text" name="nombre" class="form-control" value="<?= $producto['nombre'] ?>" required>
    </div>
    <div class="mb-3">
        <label>Stock</label>
        <input type="number" name="stock" class="form-control" value="<?= $producto['stock'] ?>" min="0" required>
    </div>
    <button name="guardar" class="btn btn-primary">Actualizar</button>
    <a href="productos.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include("footer.php"); ?>