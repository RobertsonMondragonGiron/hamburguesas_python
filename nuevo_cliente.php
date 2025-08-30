<?php
// ===== CORRECCIÓN: nuevo_cliente.php (mejorado con Bootstrap) =====
include("config/db.php");
include("header.php");
?>

<h2>➕ Registrar Cliente</h2>

<?php
if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    $stmt = $conn->prepare("INSERT INTO clientes (nombre, direccion, telefono) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $direccion, $telefono);
    $stmt->execute();

    echo "<div class='alert alert-success'>✅ Cliente registrado correctamente.</div>";
}
?>

<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label class="form-label">Nombre:</label>
        <input type="text" name="nombre" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Dirección:</label>
        <input type="text" name="direccion" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Teléfono:</label>
        <input type="text" name="telefono" class="form-control">
    </div>
    <button type="submit" name="guardar" class="btn btn-success">✅ Guardar</button>
    <a href="clientes.php" class="btn btn-secondary">⬅️ Volver</a>
</form>

<?php include("footer.php"); ?>