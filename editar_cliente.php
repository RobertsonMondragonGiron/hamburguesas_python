<?php

include("config/db.php");
include("header.php");

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM clientes WHERE id_cliente = $id");
$cliente = $result->fetch_assoc();

if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    
    $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, direccion = ?, telefono = ? WHERE id_cliente = ?");
    $stmt->bind_param("sssi", $nombre, $direccion, $telefono, $id);
    $stmt->execute();
    echo "<div class='alert alert-success'>✅ Cliente actualizado.</div>";
    $cliente['nombre'] = $nombre;
    $cliente['direccion'] = $direccion;
    $cliente['telefono'] = $telefono;
}
?>

<h2>✏️ Editar Cliente</h2>

<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label>Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?= $cliente['nombre'] ?>" required>
    </div>
    <div class="mb-3">
        <label>Dirección</label>
        <input type="text" name="direccion" class="form-control" value="<?= $cliente['direccion'] ?>">
    </div>
    <div class="mb-3">
        <label>Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?= $cliente['telefono'] ?>">
    </div>
    <button name="guardar" class="btn btn-primary">Actualizar</button>
    <a href="clientes.php" class="btn btn-secondary">Cancelar/Volver</a>
    
</form>

<?php include("footer.php"); ?>