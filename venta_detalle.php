<?php 
include("config/db.php");
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
include("header.php");
?>

<h2>📋 Detalle de Venta</h2>

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

// Obtener los detalles de la venta para el recibo
$detalle_sql = "SELECT dv.*, th.nombre, th.precio
                FROM detalle_ventas dv
                JOIN tipo_hamburguesa th ON dv.hamburguesa_id = th.id_hamburguesa
                WHERE dv.venta_id = ?
                ORDER BY th.nombre";
$stmt_detalle = $conn->prepare($detalle_sql);
$stmt_detalle->bind_param("i", $id);
$stmt_detalle->execute();
$detalle_result = $stmt_detalle->get_result();

// Calcular totales
$total_items = 0;
$productos_venta = [];
while($detalle = $detalle_result->fetch_assoc()) {
    $total_items += $detalle['cantidad'];
    $productos_venta[] = $detalle;
}
?>

<!-- Información de la venta -->
<div class="row mb-4">
    <div class="col-md-6">
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
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">📅 Fecha y Hora</h5>
            </div>
            <div class="card-body">
                <p><strong>📅 Fecha:</strong><br><?= date('d/m/Y', strtotime($venta['fecha'])) ?></p>
                <p><strong>🕒 Hora:</strong><br><?= date('H:i:s', strtotime($venta['fecha'])) ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">💰 Total de Venta</h5>
            </div>
            <div class="card-body text-center">
                <h3 class="text-success mb-0">$<?= number_format($venta['valor_total'], 2) ?></h3>
                <small class="text-muted"><?= $total_items ?> productos</small>
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
                    $total_verificacion = 0;
                    
                    foreach($productos_venta as $detalle){
                        $precio_unitario = $detalle['subtotal'] / $detalle['cantidad'];
                        $total_verificacion += $detalle['subtotal'];
                        
                        echo "<tr>
                                <td><strong>{$detalle['nombre']}</strong></td>
                                <td class='text-center'>
                                    <span class='badge bg-primary'>{$detalle['cantidad']}</span>
                                </td>
                                <td class='text-end'>$".number_format($precio_unitario, 2)."</td>
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
    </div>
</div>

<!-- RECIBO PARA EL CLIENTE (Optimizado para impresión) -->
<div class="card mt-4 d-print-block" id="recibo-cliente">
    <div class="card-header bg-info text-white d-print-none">
        <h5 class="mb-0">🧾 Recibo para el Cliente</h5>
    </div>
    <div class="card-body" id="contenido-recibo">
        
        <!-- Encabezado del establecimiento -->
        <div class="text-center mb-4">
            <h2 class="mb-1">🍔 HAMBURGUESAS FERXXO</h2>
            <p class="mb-1">Sistema de Ventas</p>
            <p class="mb-0"><small>📞 Tel: (123) 456-7890 | 📧 info@hamburguesas.com</small></p>
            <hr class="my-3">
        </div>
        
        <!-- Información de la venta -->
        <div class="row mb-3">
            <div class="col-6">
                <strong>📄 RECIBO DE VENTA</strong><br>
                
                <strong>📅 Fecha:</strong> <?= date('d/m/Y', strtotime($venta['fecha'])) ?><br>
                <strong>🕒 Hora:</strong> <?= date('H:i:s', strtotime($venta['fecha'])) ?>
            </div>
            <div class="col-6 text-end">
                <strong>👤 Cliente:</strong><br>
                <?= $venta['cliente'] ?: 'Cliente General' ?><br>
                <?php if($venta['telefono']): ?>
                    <small>📞 <?= $venta['telefono'] ?></small><br>
                <?php endif; ?>
                <?php if($venta['direccion']): ?>
                    <small>📍 <?= $venta['direccion'] ?></small><br>
                <?php endif; ?>
                
                <strong>👨‍💼 Atendido por:</strong><br>
                <?= $venta['empleado'] ?: 'No especificado' ?>
            </div>
        </div>
        
        <hr>
        
        <!-- Detalle de productos para el cliente -->
        <table class="table table-sm table-borderless">
            <thead>
                <tr class="border-bottom">
                    <th>PRODUCTO</th>
                    <th class="text-center">CANT.</th>
                    <th class="text-end">P.UNIT</th>
                    <th class="text-end">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($productos_venta as $detalle): 
                    $precio_unitario = $detalle['subtotal'] / $detalle['cantidad'];
                ?>
                <tr>
                    <td><strong><?= $detalle['nombre'] ?></strong></td>
                    <td class="text-center"><?= $detalle['cantidad'] ?></td>
                    <td class="text-end">$<?= number_format($precio_unitario, 2) ?></td>
                    <td class="text-end"><strong>$<?= number_format($detalle['subtotal'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <hr>
        
        <!-- Resumen de totales -->
        <div class="row">
            <div class="col-6">
                <p class="mb-1"><strong>📦 Total de Productos:</strong> <?= $total_items ?></p>
                <p class="mb-0"><strong>💳 Método de Pago:</strong> Efectivo</p>
            </div>
            <div class="col-6 text-end">
                <div class="border p-2 bg-light">
                    <h4 class="mb-0 text-success">
                        <strong>TOTAL A PAGAR</strong><br>
                        $<?= number_format($venta['valor_total'], 2) ?>
                    </h4>
                </div>
            </div>
        </div>
        
        <hr>
        
        <!-- Información de contacto del cliente (si existe) -->
        <?php if($venta['telefono'] || $venta['direccion']): ?>
        <div class="row mb-3">
            <div class="col-12">
                <h6><strong>📞 Datos de Contacto:</strong></h6>
                <?php if($venta['telefono']): ?>
                    <p class="mb-1"><small>📞 <strong>Teléfono:</strong> <?= $venta['telefono'] ?></small></p>
                <?php endif; ?>
                <?php if($venta['direccion']): ?>
                    <p class="mb-0"><small>📍 <strong>Dirección:</strong> <?= $venta['direccion'] ?></small></p>
                <?php endif; ?>
            </div>
        </div>
        <hr>
        <?php endif; ?>
        
        <!-- Mensaje de agradecimiento -->
        <div class="text-center mt-4">
            <h5>¡GRACIAS POR SU COMPRA!</h5>
            <p class="mb-1">Esperamos verle pronto de nuevo</p>
            <p class="mb-0"><small>📅 Fecha de impresión: <?= date('d/m/Y H:i:s') ?></small></p>
            
            <div class="mt-3 p-2 bg-light rounded">
                <small class="text-muted">
                    💡 <strong>¿Le gustó nuestro servicio?</strong><br>
                    Recomiéndenos con sus amigos y familiares<br>
                    🌟 ¡Su opinión es muy importante para nosotros! 🌟
                </small>
            </div>
        </div>
        
    </div>
</div>

<!-- Acciones -->
<div class="row mt-4 d-print-none justify-content-center">
    <div class="col-md-3">
        <a href="ventas.php" class="btn btn-secondary btn-lg w-100">
            ⬅️ Volver a Ventas
        </a>
    </div>
    <div class="col-md-3">
        <button onclick="imprimirRecibo()" class="btn btn-info btn-lg w-100">
            🖨️ Imprimir Recibo
        </button>
    </div>
</div>


<!-- Estilo para impresión optimizado -->
<style>
@media print {
    /* Ocultar todo excepto el recibo */
    body * { visibility: hidden; }
    #recibo-cliente, #recibo-cliente * { visibility: visible; }
    #contenido-recibo, #contenido-recibo * { visibility: visible; }
    
    /* Configuración de página */
    @page {
        margin: 0.5cm;
        size: A4;
    }
    
    /* Posicionar el recibo */
    #recibo-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: 2px solid #000 !important;
    }
    
    /* Estilo del contenido */
    body { 
        font-size: 14px !important; 
        line-height: 1.4 !important;
        color: #000 !important;
        background: white !important;
    }
    
    .card-body { 
        padding: 20px !important; 
    }
    
    h2, h4, h5, h6 {
        color: #000 !important;
        margin-bottom: 10px !important;
    }
    
    .table {
        margin-bottom: 15px !important;
    }
    
    .table td, .table th {
        padding: 5px !important;
        border-bottom: 1px solid #ddd !important;
    }
    
    .bg-light {
        background-color: #f8f9fa !important;
    }
    
    .text-success {
        color: #28a745 !important;
    }
    
    .border {
        border: 2px solid #000 !important;
    }
    
    /* Ocultar elementos no necesarios */
    .d-print-none { display: none !important; }
    .d-print-block { display: block !important; }
}

/* Estilo general mejorado */
#recibo-cliente {
    max-width: 800px;
    margin: 0 auto;
}

.table-borderless td {
    border: none !important;
}

.table-borderless th {
    border-bottom: 2px solid #dee2e6 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
}
</style>

<!-- JavaScript para funcionalidades de impresión -->
<script>
function imprimirRecibo() {
    // Asegurar que el recibo esté visible
    const recibo = document.getElementById('recibo-cliente');
    if (recibo) {
        // Preparar para imprimir
        document.title = 'Recibo de Venta #<?= $id ?>';
        
        // Imprimir
        window.print();
        
        // Mensaje de confirmación (se mostrará después de imprimir)
        setTimeout(function() {
            if (confirm('¿Se imprimió correctamente el recibo?')) {
                console.log('Recibo impreso exitosamente');
            }
        }, 1000);
    } else {
        alert('❌ Error: No se pudo preparar el recibo para impresión');
    }
}

// Atajo de teclado para imprimir (Ctrl+P)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        imprimirRecibo();
    }
});
</script>

<?php include("footer.php"); ?>