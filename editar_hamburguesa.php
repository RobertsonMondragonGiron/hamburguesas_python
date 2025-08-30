<?php include("config/db.php"); ?>
<?php include("header.php"); ?>

<h2>✏️ Editar Hamburguesa</h2>

<?php
$id = (int)$_GET['id'];
$res = $conn->query("SELECT * FROM tipo_hamburguesa WHERE id_hamburguesa=$id");
$hamb = $res->fetch_assoc();

if(isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stmt = $conn->prepare("UPDATE tipo_hamburguesa SET nombre=?, precio=? WHERE id_hamburguesa=?");
    $stmt->bind_param("sdi", $nombre, $precio, $id);
    $stmt->execute();
    echo "<div class='alert alert-success'>✅ Cambios guardados.</div>";
    $hamb['nombre'] = $nombre;
    $hamb['precio'] = $precio;
}
?>

<form method="POST" class="card p-3 shadow-sm">
  <div class="mb-3">
    <label>Nombre</label>
    <input type="text" name="nombre" class="form-control" value="<?= $hamb['nombre'] ?>" required>
  </div>
  <div class="mb-3">
    <label>Precio</label>
    <input type="number" step="0.01" name="precio" class="form-control" value="<?= $hamb['precio'] ?>" required>
  </div>
  <button name="guardar" class="btn btn-primary">Actualizar</button>
  <a href="tipo_hamburguesa.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include("footer.php"); ?>
