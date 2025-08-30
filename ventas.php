<?php include("config/db.php"); ?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Ventas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4">

<?php include("header.php"); ?>

<h2>📋 Ventas</h2>

<?php include("footer.php"); ?>
  
  <table class="table bg-white">
    <thead class="table-dark"><tr><th>ID</th><th>Cliente</th><th>Empleado</th><th>Fecha</th><th>Total</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php
        $sql = "SELECT v.id, v.cliente_id, v.empleado_id, v.fecha, v.valor, c.nombre AS cliente, e.nombre AS empleado 
                FROM ventas v
                LEFT JOIN clientes c ON v.cliente_id = c.id_cliente
                LEFT JOIN empleados e ON v.empleado_id = e.id_empleado
                ORDER BY v.id DESC";
        $res = $conn->query($sql);
        while($r = $res->fetch_assoc()){
          echo "<tr>
                  <td>{$r['id']}</td>
                  <td>{$r['cliente']}</td>
                  <td>{$r['empleado']}</td>
                  <td>{$r['fecha']}</td>
                  <td>$".number_format($r['valor'],2)."</td>
                  <td><a href='venta_detalle.php?id={$r['id']}' class='btn btn-sm btn-info'>Detalle</a></td>
                </tr>";
        }
      ?>
    </tbody>
  </table>
  <a href="index.php" class="btn btn-secondary">Volver</a>
</div>
</body>
</html>
