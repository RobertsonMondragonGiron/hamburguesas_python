<?php include("config/db.php"); ?>
<?php include("header.php"); ?>

<h2>➕ Nueva Hamburguesa</h2>

<?php
if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stmt = $conn->prepare("INSERT INTO tipo_hamburguesa (nombre, precio) VALUES (?, ?)");
    $stmt->bind_param("sd", $nombre, $precio);
    $stmt->execute();
    echo "<div class='alert alert-success'>✅ Hamburguesa registrada.</div>";
}
?>

<form method="POST" class="card p-3 shadow-sm">
  <div class="mb-3">
    <label>Nombre</label>
    <input type="text" name="nombre" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Precio</label>
    <input type="number" step="0.01" name="precio" class="form-control" required>
  </div>
  <button name="guardar" class="btn btn-success">Guardar</button>
  <a href="tipo_hamburguesa.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include("footer.php"); ?>
