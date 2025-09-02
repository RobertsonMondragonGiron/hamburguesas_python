<?php
include("config/db.php");
include("header.php");

// Procesar la venta
if(isset($_POST['procesar_venta'])){
    $cliente_id = (int)$_POST['cliente_id'];
    $empleado_id = (int)$_POST['empleado_id'];
    $items = json_decode($_POST['items_json'], true);
    $total = (float)$_POST['total'];
    $monto_recibido = (float)$_POST['monto_recibido'];
    $cambio = $monto_recibido - $total;
    
    if(empty($items) || $total <= 0){
        echo "<div class='alert alert-danger'>❌ Error: Carrito vacío o total inválido.</div>";
    } else if($monto_recibido < $total) {
        echo "<div class='alert alert-danger'>❌ Error: Monto insuficiente.</div>";
    } else {
        try {
            $conn->begin_transaction();
            
            // Insertar venta (solo datos básicos)
            $stmt = $conn->prepare("INSERT INTO ventas (cliente_id, empleado_id, fecha, valor_total) VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("iid", $cliente_id, $empleado_id, $total);
            $stmt->execute();
            $venta_id = $conn->insert_id;
            
            // Insertar detalles
            $stmt_detalle = $conn->prepare("INSERT INTO detalle_ventas (venta_id, hamburguesa_id, cantidad, subtotal) VALUES (?, ?, ?, ?)");
            foreach($items as $item){
                $stmt_detalle->bind_param("iiid", $venta_id, $item['id'], $item['cantidad'], $item['subtotal']);
                $stmt_detalle->execute();
                
                // Descontar stock de ingredientes
                $ingredientes_sql = "SELECT hp.producto_id, hp.cantidad 
                                   FROM hamburguesa_producto hp 
                                   WHERE hp.hamburguesa_id = ?";
                $stmt_ing = $conn->prepare($ingredientes_sql);
                $stmt_ing->bind_param("i", $item['id']);
                $stmt_ing->execute();
                $ingredientes = $stmt_ing->get_result();
                
                while($ing = $ingredientes->fetch_assoc()){
                    $cantidad_usar = $ing['cantidad'] * $item['cantidad'];
                    $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
                    $stmt_stock->bind_param("ii", $cantidad_usar, $ing['producto_id']);
                    $stmt_stock->execute();
                }
            }
            
            $conn->commit();
            
            // Mensaje de éxito con información de pago (solo visual)
            echo "<div class='alert alert-success'>
                    ✅ <strong>¡Venta #$venta_id procesada exitosamente!</strong>
                    <div class='mt-3'>
                        <div class='row'>
                            <div class='col-md-4'>
                                <strong>💰 Total de la venta:</strong><br>
                                <span class='h4 text-success'>$".number_format($total, 2)."</span>
                            </div>
                            <div class='col-md-4'>
                                <strong>💵 Monto recibido:</strong><br>
                                <span class='h4 text-primary'>$".number_format($monto_recibido, 2)."</span>
                            </div>
                            <div class='col-md-4'>
                                <strong>💰 Cambio a entregar:</strong><br>
                                <span class='h4 text-info'>$".number_format($cambio, 2)."</span>
                            </div>
                        </div>
                    </div>
                    <div class='mt-3'>
                        <a href='venta_detalle.php?id=$venta_id' class='btn btn-info'>👁️ Ver Detalle</a>
                        <a href='ventas.php' class='btn btn-success'>📋 Ver Ventas</a>
                        <a href='nueva_venta.php' class='btn btn-primary'>➕ Nueva Venta</a>
                        <button onclick='window.print()' class='btn btn-secondary'>🖨️ Imprimir</button>
                    </div>
                  </div>";
            
            // Limpiar carrito después de procesar (JavaScript)
            echo "<script>
                    setTimeout(function() {
                        if(typeof actualizarCarrito === 'function') {
                            carrito = [];
                            document.getElementById('monto_recibido').value = '';
                            actualizarCarrito();
                        }
                    }, 100);
                  </script>";
            
        } catch(Exception $e) {
            $conn->rollback();
            echo "<div class='alert alert-danger'>❌ Error: ".$e->getMessage()."</div>";
        }
    }
}
?>

<h2>➕ Nueva Venta</h2>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5>🛒 Carrito de Compras</h5>
            </div>
            <div class="card-body">
                
                <!-- Cliente y Empleado -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label><strong>👤 Cliente</strong></label>
                        <select id="cliente_id" class="form-select">
                            <option value="">Seleccionar cliente...</option>
                            <?php
                            $clientes = $conn->query("SELECT id_cliente, nombre, direccion FROM clientes ORDER BY nombre");
                            while($c = $clientes->fetch_assoc()){
                                echo "<option value='{$c['id_cliente']}'>{$c['nombre']} - {$c['direccion']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label><strong>👨‍💼 Empleado</strong></label>
                        <select id="empleado_id" class="form-select">
                            <option value="">Seleccionar empleado...</option>
                            <?php
                            $empleados = $conn->query("SELECT id_empleado, nombre, apellido FROM empleados ORDER BY nombre");
                            while($e = $empleados->fetch_assoc()){
                                echo "<option value='{$e['id_empleado']}'>{$e['nombre']} {$e['apellido']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Agregar Producto -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>🍔 Hamburguesa</strong></label>
                        <select id="hamburguesa_select" class="form-select">
                            <option value="">Seleccionar hamburguesa...</option>
                            <?php
                            $hamburguesas = $conn->query("SELECT id_hamburguesa, nombre, precio FROM tipo_hamburguesa ORDER BY nombre");
                            while($h = $hamburguesas->fetch_assoc()){
                                echo "<option value='{$h['id_hamburguesa']}' data-precio='{$h['precio']}' data-nombre='{$h['nombre']}'>";
                                echo "{$h['nombre']} - $".number_format($h['precio'], 2);
                                echo "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label><strong>Cantidad</strong></label>
                        <input type="number" id="cantidad_input" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button id="agregar_btn" class="btn btn-primary w-100">➕ Agregar</button>
                    </div>
                </div>

                <!-- Tabla Carrito -->
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="carrito_body">
                        <tr id="carrito_vacio">
                            <td colspan="5" class="text-center text-muted">🛒 Carrito vacío</td>
                        </tr>
                    </tbody>
                    <tfoot id="carrito_total" style="display: none;">
                        <tr class="table-success">
                            <th colspan="3">TOTAL:</th>
                            <th id="total_display">$0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

                <!-- Pago -->
                <div id="pago_section" style="display: none;" class="mt-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning">
                            <h6>💰 Información de Pago</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label><strong>Total a Pagar:</strong></label>
                                    <div id="total_pagar" class="form-control bg-light text-center h5">$0.00</div>
                                </div>
                                <div class="col-md-4">
                                    <label><strong>Monto Recibido:</strong></label>
                                    <input type="number" id="monto_recibido" class="form-control text-center" step="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label><strong>Cambio:</strong></label>
                                    <div id="cambio_display" class="form-control bg-info text-white text-center h5">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <button id="limpiar_btn" class="btn btn-outline-danger w-100" disabled>🗑️ Limpiar</button>
                    </div>
                    <div class="col-md-6">
                        <button id="procesar_btn" class="btn btn-success w-100" disabled>💰 Procesar Venta</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Panel lateral -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6>📊 Resumen</h6>
            </div>
            <div class="card-body text-center">
                <h4 id="total_items" class="text-primary">0</h4>
                <small>Items en carrito</small>
                <hr>
                <h4 id="total_resumen" class="text-success">$0.00</h4>
                <small>Total</small>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto -->
<form id="venta_form" method="POST" style="display: none;">
    <input type="hidden" id="form_cliente_id" name="cliente_id">
    <input type="hidden" id="form_empleado_id" name="empleado_id">
    <input type="hidden" id="form_items_json" name="items_json">
    <input type="hidden" id="form_total" name="total">
    <input type="hidden" id="form_monto_recibido" name="monto_recibido">
    <input type="hidden" name="procesar_venta" value="1">
</form>

<div class="mt-3">
    <a href="ventas.php" class="btn btn-secondary">⬅️ Volver</a>
</div>

<!-- JavaScript SIMPLE y FUNCIONAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sistema iniciado');
    
    // Variables
    let carrito = [];
    let total = 0;
    
    // Elementos
    const agregarBtn = document.getElementById('agregar_btn');
    const hamburguesaSelect = document.getElementById('hamburguesa_select');
    const cantidadInput = document.getElementById('cantidad_input');
    const carritoBody = document.getElementById('carrito_body');
    const carritoVacio = document.getElementById('carrito_vacio');
    const carritoTotal = document.getElementById('carrito_total');
    const totalDisplay = document.getElementById('total_display');
    const pagoSection = document.getElementById('pago_section');
    const montoRecibido = document.getElementById('monto_recibido');
    const cambioDisplay = document.getElementById('cambio_display');
    const procesarBtn = document.getElementById('procesar_btn');
    const limpiarBtn = document.getElementById('limpiar_btn');
    
    // AGREGAR AL CARRITO
    agregarBtn.onclick = function() {
        console.log('🔥 Botón clickeado!');
        
        const selectElement = hamburguesaSelect;
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        if(!selectElement.value) {
            alert('❌ Selecciona una hamburguesa');
            return;
        }
        
        const cantidad = parseInt(cantidadInput.value) || 1;
        const hamburguesaId = parseInt(selectElement.value);
        const nombre = selectedOption.dataset.nombre;
        const precio = parseFloat(selectedOption.dataset.precio);
        const subtotal = precio * cantidad;
        
        // Buscar si ya existe
        let found = false;
        for(let i = 0; i < carrito.length; i++) {
            if(carrito[i].id === hamburguesaId) {
                carrito[i].cantidad += cantidad;
                carrito[i].subtotal = carrito[i].cantidad * carrito[i].precio;
                found = true;
                break;
            }
        }
        
        // Si no existe, agregarlo
        if(!found) {
            carrito.push({
                id: hamburguesaId,
                nombre: nombre,
                precio: precio,
                cantidad: cantidad,
                subtotal: subtotal
            });
        }
        
        actualizarCarrito();
        selectElement.value = '';
        cantidadInput.value = 1;
        
        console.log('✅ Producto agregado:', nombre);
    };
    
    // ACTUALIZAR CARRITO
    function actualizarCarrito() {
        carritoBody.innerHTML = '';
        total = 0;
        let totalItems = 0;
        
        if(carrito.length === 0) {
            carritoBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">🛒 Carrito vacío</td></tr>';
            carritoTotal.style.display = 'none';
            pagoSection.style.display = 'none';
            limpiarBtn.disabled = true;
            procesarBtn.disabled = true;
        } else {
            for(let i = 0; i < carrito.length; i++) {
                const item = carrito[i];
                total += item.subtotal;
                totalItems += item.cantidad;
                
                carritoBody.innerHTML += 
                    '<tr>' +
                        '<td><strong>' + item.nombre + '</strong></td>' +
                        '<td><span class="badge bg-primary">' + item.cantidad + '</span></td>' +
                        '<td>$' + item.precio.toFixed(2) + '</td>' +
                        '<td><strong>$' + item.subtotal.toFixed(2) + '</strong></td>' +
                        '<td><button class="btn btn-sm btn-danger" onclick="eliminarItem(' + i + ')">🗑️</button></td>' +
                    '</tr>';
            }
            
            carritoTotal.style.display = 'table-row-group';
            pagoSection.style.display = 'block';
            limpiarBtn.disabled = false;
        }
        
        totalDisplay.textContent = '$' + total.toFixed(2);
        document.getElementById('total_pagar').textContent = '$' + total.toFixed(2);
        document.getElementById('total_items').textContent = totalItems;
        document.getElementById('total_resumen').textContent = '$' + total.toFixed(2);
        
        calcularCambio();
    }
    
    // CALCULAR CAMBIO
    function calcularCambio() {
        const recibido = parseFloat(montoRecibido.value) || 0;
        const cambio = recibido - total;
        
        if(cambio >= 0 && carrito.length > 0) {
            cambioDisplay.textContent = '$' + cambio.toFixed(2);
            cambioDisplay.className = 'form-control bg-success text-white text-center h5';
            procesarBtn.disabled = false;
        } else {
            cambioDisplay.textContent = cambio < 0 ? '$' + Math.abs(cambio).toFixed(2) + ' (Falta)' : '$0.00';
            cambioDisplay.className = 'form-control bg-danger text-white text-center h5';
            procesarBtn.disabled = true;
        }
    }
    
    // EVENTOS
    montoRecibido.addEventListener('input', calcularCambio);
    
    // LIMPIAR CARRITO
    limpiarBtn.onclick = function() {
        if(confirm('¿Limpiar carrito?')) {
            carrito = [];
            montoRecibido.value = '';
            actualizarCarrito();
        }
    };
    
    // PROCESAR VENTA
    procesarBtn.onclick = function() {
        const clienteId = document.getElementById('cliente_id').value;
        const empleadoId = document.getElementById('empleado_id').value;
        const montoRecibidoVal = parseFloat(montoRecibido.value);
        
        if(!clienteId || !empleadoId) {
            alert('❌ Selecciona cliente y empleado');
            return;
        }
        
        if(carrito.length === 0) {
            alert('❌ Carrito vacío');
            return;
        }
        
        if(montoRecibidoVal < total) {
            alert('❌ Monto insuficiente');
            return;
        }
        
        if(confirm('¿Procesar venta por $' + total.toFixed(2) + '?')) {
            document.getElementById('form_cliente_id').value = clienteId;
            document.getElementById('form_empleado_id').value = empleadoId;
            document.getElementById('form_items_json').value = JSON.stringify(carrito);
            document.getElementById('form_total').value = total.toFixed(2);
            document.getElementById('form_monto_recibido').value = montoRecibidoVal.toFixed(2);
            
            document.getElementById('venta_form').submit();
        }
    };
    
    // ELIMINAR ITEM (función global)
    window.eliminarItem = function(index) {
        carrito.splice(index, 1);
        actualizarCarrito();
    };
    
    console.log('✅ Sistema listo');
});
</script>

<?php include("footer.php"); ?>