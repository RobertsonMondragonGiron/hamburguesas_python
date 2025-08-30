<?php
// ===== ARCHIVO: ventas.php (CORREGIDO) =====
?>
<!--- Separador para ventas.php --->
<?php 
include("config/db.php");
include("header.php");
?>

<h2>📋 Ventas</h2>

<a href="nueva_venta.php" class="btn btn-success mb-3">➕ Nueva Venta</a>

<table class="table table-striped bg-white shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Empleado</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT v.id_venta, v.cliente_id, v.empleado_id, v.fecha, v.valor_total, 
                       c.nombre AS cliente, e.nombre AS empleado 
                FROM ventas v
                LEFT JOIN clientes c ON v.cliente_id = c.id_cliente
                LEFT JOIN empleados e ON v.empleado_id = e.id_empleado
                ORDER BY v.id_venta DESC";
        
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
            echo "<tr>
                    <td>{$row['id_venta']}</td>
                    <td>{$row['cliente']}</td>
                    <td>{$row['empleado']}</td>
                    <td>".date('d/m/Y H:i', strtotime($row['fecha']))."</td>
                    <td>$".number_format($row['valor_total'], 2)."</td>
                    <td>
                        <a href='venta_detalle.php?id={$row['id_venta']}' class='btn btn-sm btn-info'>👁️ Ver Detalle</a>
                    </td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<?php include("footer.php"); ?>

<?php
// ===== ARCHIVO: venta_detalle.php (CORREGIDO) =====
?>
<!--- Separador para venta_detalle.php --->
<?php 
include("config/db.php");
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
include("header.php");
?>

<h2>📋 Detalle de Venta #<?= $id ?></h2>

<?php
// Obtener información de la venta
$venta_sql = "SELECT v.*, c.nombre AS cliente, e.nombre AS empleado 
              FROM ventas v
              LEFT JOIN clientes c ON v.cliente_id = c.id_cliente
              LEFT JOIN empleados e ON v.empleado_id = e.id_empleado
              WHERE v.id_venta = $id";
$venta_result = $conn->query($venta_sql);
$venta = $venta_result->fetch_assoc();

if($venta):
?>
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Cliente:</strong> <?= $venta['cliente'] ?></p>
                <p><strong>Empleado:</strong> <?= $venta['empleado'] ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></p>
                <p><strong>Total:</strong> $<?= number_format($venta['valor_total'], 2) ?></p>
            </div>
        </div>
    </div>
</div>

<h4>Items de la Venta</h4>
<table class="table table-striped bg-white">
    <thead class="table-secondary">
        <tr><th>Hamburguesa</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr>
    </thead>
    <tbody>
        <?php
        $detalle_sql = "SELECT dv.*, th.nombre, th.precio
                        FROM detalle_ventas dv
                        JOIN tipo_hamburguesa th ON dv.hamburguesa_id = th.id_hamburguesa
                        WHERE dv.venta_id = $id";
        $detalle_result = $conn->query($detalle_sql);
        $total_verificacion = 0;
        
        while($detalle = $detalle_result->fetch_assoc()){
            $precio_unitario = $detalle['subtotal'] / $detalle['cantidad'];
            $total_verificacion += $detalle['subtotal'];
            echo "<tr>
                    <td>{$detalle['nombre']}</td>
                    <td>{$detalle['cantidad']}</td>
                    <td>$".number_format($precio_unitario, 2)."</td>
                    <td>$".number_format($detalle['subtotal'], 2)."</td>
                  </tr>";
        }
        ?>
    </tbody>
    <tfoot>
        <tr class="table-secondary">
            <th colspan="3" class="text-end">Total</th>
            <th>$<?= number_format($total_verificacion, 2) ?></th>
        </tr>
    </tfoot>
</table>

<?php else: ?>
<div class="alert alert-danger">Venta no encontrada.</div>
<?php endif; ?>

<a href="ventas.php" class="btn btn-secondary">⬅️ Volver a Ventas</a>

<?php include("footer.php"); ?>