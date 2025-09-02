<?php

include("config/db.php");
include("header.php");

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM empleados WHERE id_empleado = $id");
$empleado = $result->fetch_assoc();

if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cargo = $_POST['cargo'];
    
    $stmt = $conn->prepare("UPDATE empleados SET nombre = ?, apellido = ?, cargo = ? WHERE id_empleado = ?");
    $stmt->bind_param("sssi", $nombre, $apellido, $cargo, $id);
    $stmt->execute();
    echo "<div class='alert alert-success'>✅ Empleado actualizado.</div>";
    $empleado['nombre'] = $nombre;
    $empleado['apellido'] = $apellido;
    $empleado['cargo'] = $cargo;
}
?>

<h2>✏️ Editar Empleado</h2>

<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label>Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?= $empleado['nombre'] ?>" required>
    </div>
    <div class="mb-3">
        <label>Apellido</label>
        <input type="text" name="apellido" class="form-control" value="<?= $empleado['apellido'] ?>" required>
    </div>
    <div class="mb-3">
        <label>Cargo</label>
        <input type="text" name="cargo" class="form-control" value="<?= $empleado['cargo'] ?>" required>
    </div>
    <button name="guardar" class="btn btn-primary">Actualizar</button>
    <a href="empleados.php" class="btn btn-secondary">Cancelar/Volver</a>
</form>

<?php include("footer.php"); ?>