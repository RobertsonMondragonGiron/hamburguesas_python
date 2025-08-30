<?php include("config/db.php");
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Detalle venta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

<?php include("header.php"); ?>

<h2>Detalle de venta #<?= $id ?></h2>

<?php include("footer.php"); ?>
  
  <table class="table bg-white">
    <thead class="table-secondary">
      <tr><th>Hamburguesa</th><th>Cantidad</th><th>Subtotal</th></tr>
    </thead>
    <tbody>
      <?php
        $sql = "SELECT dv.*, th.nombre 
                FROM detalle_ventas dv
                JOIN tipo_hamburguesa th ON dv.hamburguesa_id = th.id_hamburguesa
                WHERE dv.venta_id = $id";
        $res = $conn->query($sql);
        $total = 0;
        while($r = $res->fetch_assoc()){
          $total += $r['subtotal'];
          echo "<tr>
                  <td>{$r['nombre']}</td>
                  <td>{$r['cantidad']}</td>
                  <td>$".number_format($r['subtotal'],2)."</td>
                </tr>";
        }
      ?>
    </tbody>
    <tfoot>
      <tr><th colspan="2" class="text-end">Total</th><th>$<?=number_format($total,2)?></th></tr>
    </tfoot>
  </table>
  <a href="ventas.php" class="btn btn-secondary">Volver</a>
</div>
</body>
</html>
