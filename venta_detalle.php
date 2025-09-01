<?php 
include("config/db.php");
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
include("header.php");
?>

<h2>📋 Detalle de Venta #<?= $id ?></h2>

<?php
// Obtener información de la venta
$venta_sql = "SELECT v.*, c.nombre AS cliente, c.direccion, c.telefono, e.nombre AS empleado, e.cargo
              FROM ventas v
              LEFT JOIN clientes c ON v.cliente_id = c.id_cliente
              LEFT JOIN empleados e ON v.empleado_id = e.id_empleado
              WHERE v.id_venta = ?";
$stmt = $conn->prepare($venta_sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$venta = $result->fetch_assoc();

if(!$venta):
?>
    <div class="alert alert-danger">
        ❌ <strong>Venta no encontrada</strong>
        <br>La venta con ID #<?= $id ?> no existe en el sistema.
        <br><a href="ventas.php" class="btn btn-primary mt-2">⬅️ Volver a Ventas</a>
    </div>
<?php 
include("footer.php");
exit;
endif;
?>

<!-- Información de la venta -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📄 Información de la Venta</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>👤 Cliente</h6>
                        <p class="mb-1"><strong><?= $venta['cliente'] ?: 'Cliente no especificado' ?></strong></p>
                        <?php if($venta['direccion']): ?>
                            <p class="mb-1"><small>📍 <?= $venta['direccion'] ?></small></p>
                        <?php endif; ?>
                        <?php if($venta['telefono']): ?>
                            <p class="mb-0"><small>📞 <?= $venta['telefono'] ?></small></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>👨‍💼 Empleado</h6>
                        <p class="mb-1"><strong><?= $venta['empleado'] ?: 'No especificado' ?></strong></p>
                        <?php if($venta['cargo']): ?>
                            <p class="mb-0"><small>🏷️ <?= $venta['cargo'] ?></small></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">💰 Resumen</h5>
            </div>
            <div class="card-body">
                <p><strong>📅 Fecha:</strong><br><?= date('d/m/Y', strtotime($venta['fecha'])) ?></p>
                <p><strong>🕒 Hora:</strong><br><?= date('H:i:s', strtotime($venta['fecha'])) ?></p>
                <p><strong>💵 Total:</strong><br>
                    <span class="h4 text-success">$<?= number_format($venta['valor_total'], 2) ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de productos -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">🍔 Productos Vendidos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-secondary">
                    <tr>
                        <th width="50%">Hamburguesa</th>
                        <th width="15%" class="text-center">Cantidad</th>
                        <th width="20%" class="text-end">Precio Unit.</th>
                        <th width="15%" class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $detalle_sql = "SELECT dv.*, th.nombre, th.precio
                                    FROM detalle_ventas dv
                                    JOIN tipo_hamburguesa th ON dv.hamburguesa_id = th.id_hamburguesa
                                    WHERE dv.venta_id = ?
                                    ORDER BY th.nombre";
                    $stmt_detalle = $conn->prepare($detalle_sql);
                    $stmt_detalle->bind_param("i", $id);
                    $stmt_detalle->execute();
                    $detalle_result = $stmt_detalle->get_result();
                    
                    $total_verificacion = 0;
                    $total_items = 0;
                    
                    while($detalle = $detalle_result->fetch_assoc()){
                        $precio_unitario = $detalle['subtotal'] / $detalle['cantidad'];
                        $total_verificacion += $detalle['subtotal'];
                        $total_items += $detalle['cantidad'];
                        
                        echo "<tr>
                                <td>
                                    <strong>{$detalle['nombre']}</strong>
                                </td>
                                <td class='text-center'>
                                    <span class='badge bg-primary'>{$detalle['cantidad']}</span>
                                </td>
                                <td class='text-end'>
                                    $".number_format($precio_unitario, 2)."
                                </td>
                                <td class='text-end'>
                                    <strong>$".number_format($detalle['subtotal'], 2)."</strong>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>TOTAL</th>
                        <th class="text-center">
                            <span class="badge bg-success"><?= $total_items ?> items</span>
                        </th>
                        <th></th>
                        <th class="text-end h5 text-success">
                            $<?= number_format($total_verificacion, 2) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Verificación de totales -->
        <?php if(abs($total_verificacion - $venta['valor_total']) > 0.01): ?>
            <div class="alert alert-warning mt-3">
                ⚠️ <strong>Advertencia:</strong> Hay una diferencia entre el total de la venta 
                ($<?= number_format($venta['valor_total'], 2) ?>) y la suma de los detalles 
                ($<?= number_format($total_verificacion, 2) ?>).
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ingredientes utilizados -->
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">🥬 Ingredientes Utilizados</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Ingrediente</th>
                        <th class="text-center">Cantidad Usada</th>
                        <th class="text-center">Stock Actual</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ingredientes_sql = "SELECT p.nombre, 
                                                SUM(hp.cantidad * dv.cantidad) as cantidad_usada,
                                                p.stock
                                         FROM detalle_ventas dv
                                         JOIN hamburguesa_producto hp ON dv.hamburguesa_id = hp.hamburguesa_id
                                         JOIN productos p ON hp.producto_id = p.id_producto
                                         WHERE dv.venta_id = ?
                                         GROUP BY p.id_producto, p.nombre, p.stock
                                         ORDER BY p.nombre";
                    $stmt_ing = $conn->prepare($ingredientes_sql);
                    $stmt_ing->bind_param("i", $id);
                    $stmt_ing->execute();
                    $ingredientes_result = $stmt_ing->get_result();
                    
                    while($ing = $ingredientes_result->fetch_assoc()){
                        if($ing['stock'] <= 5){
                            $estado = "<span class='badge bg-danger'>🔴 Crítico</span>";
                        } else if($ing['stock'] <= 10){
                            $estado = "<span class='badge bg-warning text-dark'>⚠️ Bajo</span>";
                        } else {
                            $estado = "<span class='badge bg-success'>✅ OK</span>";
                        }
                        
                        echo "<tr>
                                <td><strong>{$ing['nombre']}</strong></td>
                                <td class='text-center'>{$ing['cantidad_usada']}</td>
                                <td class='text-center'>{$ing['stock']}</td>
                                <td class='text-center'>$estado</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Acciones -->
<div class="row mt-4">
    <div class="col-md-6">
        <a href="ventas.php" class="btn btn-secondary btn-lg w-100">
            ⬅️ Volver a Ventas
        </a>
    </div>
    <div class="col-md-6">
        <button onclick="window.print()" class="btn btn-info btn-lg w-100">
            🖨️ Imprimir Recibo
        </button>
    </div>
</div>

<!-- Estilo para impresión -->
<style>
@media print {
    .btn, .navbar, .alert { display: none !important; }
    .card { border: 1px solid #000 !important; box-shadow: none !important; }
    body { font-size: 12px; }
}
</style>

<?php include("footer.php"); ?>