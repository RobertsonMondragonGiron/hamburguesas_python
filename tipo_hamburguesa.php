<?php include("config/db.php"); ?>
<?php include("header.php"); ?>

<h2>🍔 Tipos de Hamburguesa</h2>
<a href="nueva_hamburguesa.php" class="btn btn-success mb-3">➕ Nueva Hamburguesa</a>

<table class="table table-striped table-hover bg-white shadow-sm">
  <thead class="table-dark">
    <tr>
      
      <th>Nombre</th>
      <th>Precio</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $res = $conn->query("SELECT * FROM tipo_hamburguesa ORDER BY id_hamburguesa");
    while($r = $res->fetch_assoc()){
        echo "<tr>
                
                <td>{$r['nombre']}</td>
                <td>$".number_format($r['precio'],2)."</td>
                <td>
                  <a href='editar_hamburguesa.php?id={$r['id_hamburguesa']}' class='btn btn-sm btn-primary'>✏️ Editar</a>
                  <a href='tipo_hamburguesa.php?eliminar={$r['id_hamburguesa']}' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Eliminar hamburguesa?')\">🗑️ Eliminar</a>
                  <a href='hamburguesa_productos.php?id={$r['id_hamburguesa']}' class='btn btn-sm btn-warning'>🍞 Ingredientes</a>
                </td>
              </tr>";
    }
    ?>
  </tbody>
</table>

<?php
// eliminar hamburguesa
if(isset($_GET['eliminar'])){
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM tipo_hamburguesa WHERE id_hamburguesa=$id");
    echo "<script>window.location='tipo_hamburguesa.php';</script>";
}
?>

<?php include("footer.php"); ?>
