<?php include("config/db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
<?php include("header.php"); ?>

<h2>👤 Clientes</h2>

<?php
// Eliminar cliente y todos sus registros asociados
if(isset($_GET['eliminar'])){
    $id = (int)$_GET['eliminar'];
    
    // Obtener información del cliente antes de eliminar
    $cliente_info = $conn->query("SELECT nombre FROM clientes WHERE id_cliente = $id")->fetch_assoc();
    $ventas_info = $conn->query("SELECT COUNT(*) as total FROM ventas WHERE cliente_id = $id")->fetch_assoc();
    
    if($cliente_info){
        $conn->begin_transaction();
        try {
            // 1. Eliminar detalles de ventas del cliente
            $conn->query("DELETE dv FROM detalle_ventas dv 
                         INNER JOIN ventas v ON dv.venta_id = v.id_venta 
                         WHERE v.cliente_id = $id");
            
            // 2. Eliminar todas las ventas del cliente
            $conn->query("DELETE FROM ventas WHERE cliente_id = $id");
            
            // 3. Finalmente eliminar el cliente
            $conn->query("DELETE FROM clientes WHERE id_cliente = $id");
            
            $conn->commit();
            
            $nombre = $cliente_info['nombre'];
            $num_ventas = $ventas_info['total'];
            echo "<div class='alert alert-success'>
                    ✅ Cliente <strong>$nombre</strong> eliminado correctamente.
                    <br>📊 Se eliminaron también $num_ventas venta(s) y todos sus detalles asociados.
                  </div>";
            echo "<script>setTimeout(() => window.location='clientes.php', 2500);</script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div class='alert alert-danger'>
                    ❌ Error al eliminar el cliente: " . $e->getMessage() . "
                  </div>";
        }
    } else {
        echo "<div class='alert alert-warning'>⚠️ Cliente no encontrado.</div>";
    }
}
?>

<a href="nuevo_cliente.php" class="btn btn-success mb-3">➕ Nuevo Cliente</a>

<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Ventas</th>
            <th>Total Comprado</th>
            <th>Última Compra</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $sql = "SELECT c.*, 
                   COUNT(v.id_venta) as num_ventas,
                   COALESCE(SUM(v.valor_total), 0) as total_comprado,
                   MAX(v.fecha) as ultima_compra
            FROM clientes c 
            LEFT JOIN ventas v ON c.id_cliente = v.cliente_id 
            GROUP BY c.id_cliente 
            ORDER BY c.nombre";
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()){
        $ultima_compra = $row['ultima_compra'] ? date('d/m/Y', strtotime($row['ultima_compra'])) : 'Nunca';
        
        echo "<tr>
                <td>".$row['id_cliente']."</td>
                <td><strong>".$row['nombre']."</strong></td>
                <td>".$row['direccion']."</td>
                <td>".$row['telefono']."</td>
                <td><span class='badge bg-primary'>".$row['num_ventas']."</span></td>
                <td><strong>$".number_format($row['total_comprado'], 2)."</strong></td>
                <td><small>$ultima_compra</small></td>
                <td>
                    <a href='editar_cliente.php?id=".$row['id_cliente']."' 
                       class='btn btn-sm btn-primary' 
                       title='Editar información del cliente'>✏️ Editar</a>
                    
                    <a href='clientes.php?eliminar=".$row['id_cliente']."' 
                       class='btn btn-sm btn-danger' 
                       onclick=\"return confirm('⚠️ ELIMINAR CLIENTE\\n\\nEsto eliminará:\\n• Cliente: ".$row['nombre']."\\n• Sus ".$row['num_ventas']." ventas\\n• Todos los detalles asociados\\n\\n¿Está seguro?')\"
                       title='Eliminar cliente y todos sus registros'>
                       🗑️ Eliminar Todo
                    </a>
                </td>
              </tr>";
    }
    ?>
    </tbody>
</table>

<div class="mt-3">
    <div class="alert alert-info">
        <strong>ℹ️ Información:</strong> Al eliminar un cliente se eliminan automáticamente:
        <ul class="mb-0 mt-2">
            <li>📋 Todas sus ventas registradas</li>
            <li>🍔 Todos los detalles de esas ventas</li>
            <li>👤 La información personal del cliente</li>
        </ul>
    </div>
</div>

<a href="index.php" class="btn btn-secondary">⬅️ Volver</a>

</div>
</body>
</html>