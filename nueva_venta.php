<?php
include("config/db.php");
include("header.php");

// Procesar la venta cuando se envía el formulario
if(isset($_POST['procesar_venta'])){
    $cliente_id = (int)$_POST['cliente_id'];
    $empleado_id = (int)$_POST['empleado_id'];
    $items = json_decode($_POST['items_json'], true);
    $total = (float)$_POST['total'];
    
    if(empty($items) || $total <= 0){
        echo "<div class='alert alert-danger'>❌ Error: No hay productos en el carrito o el total es inválido.</div>";
    } else {
        // VALIDAR STOCK ANTES DE PROCESAR
        $stock_errors = [];
        foreach($items as $item){
            $hamburguesa_id = (int)$item['id'];
            $cantidad_solicitada = (int)$item['cantidad'];
            
            // Verificar stock de ingredientes para esta hamburguesa
            $stock_check = $conn->query("SELECT p.nombre, hp.cantidad as cantidad_por_hamburguesa, p.stock,
                                               FLOOR(p.stock / hp.cantidad) as hamburguesas_posibles
                                        FROM hamburguesa_producto hp
                                        JOIN productos p ON hp.producto_id = p.id_producto
                                        WHERE hp.hamburguesa_id = $hamburguesa_id");
            
            while($stock_row = $stock_check->fetch_assoc()){
                if($stock_row['hamburguesas_posibles'] < $cantidad_solicitada){
                    $stock_errors[] = "❌ {$item['nombre']}: Ingrediente '{$stock_row['nombre']}' insuficiente. Stock: {$stock_row['stock']}, necesario: ".($cantidad_solicitada * $stock_row['cantidad_por_hamburguesa']);
                }
            }
        }
        
        if(!empty($stock_errors)){
            echo "<div class='alert alert-danger'>
                    <strong>❌ No se puede procesar la venta por falta de stock:</strong><br>
                    ".implode('<br>', $stock_errors)."
                  </div>";
        } else {
            // PROCESAR LA VENTA
            $conn->begin_transaction();
            try {
                // 1. Insertar la venta
                $stmt_venta = $conn->prepare("INSERT INTO ventas (cliente_id, empleado_id, fecha, valor_total) VALUES (?, ?, NOW(), ?)");
                $stmt_venta->bind_param("iid", $cliente_id, $empleado_id, $total);
                $stmt_venta->execute();
                $venta_id = $conn->insert_id;
                
                // 2. Insertar los detalles de la venta y actualizar stock
                $stmt_detalle = $conn->prepare("INSERT INTO detalle_ventas (venta_id, hamburguesa_id, cantidad, subtotal) VALUES (?, ?, ?, ?)");
                $ingredientes_usados = [];
                
                foreach($items as $item){
                    $hamburguesa_id = (int)$item['id'];
                    $cantidad = (int)$item['cantidad'];
                    $subtotal = (float)$item['subtotal'];
                    
                    // Insertar detalle de venta
                    $stmt_detalle->bind_param("iiid", $venta_id, $hamburguesa_id, $cantidad, $subtotal);
                    $stmt_detalle->execute();
                    
                    // 3. Obtener ingredientes y descontar del stock
                    $ingredientes_sql = "SELECT hp.producto_id, hp.cantidad, p.nombre
                                        FROM hamburguesa_producto hp 
                                        JOIN productos p ON hp.producto_id = p.id_producto
                                        WHERE hp.hamburguesa_id = ?";
                    $stmt_ing = $conn->prepare($ingredientes_sql);
                    $stmt_ing->bind_param("i", $hamburguesa_id);
                    $stmt_ing->execute();
                    $ingredientes = $stmt_ing->get_result();
                    
                    while($ing = $ingredientes->fetch_assoc()){
                        $cantidad_usar = $ing['cantidad'] * $cantidad;
                        
                        // Actualizar stock
                        $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
                        $stmt_stock->bind_param("ii", $cantidad_usar, $ing['producto_id']);
                        $stmt_stock->execute();
                        
                        // Registrar para mostrar en el mensaje
                        $key = $ing['producto_id'];
                        if(isset($ingredientes_usados[$key])){
                            $ingredientes_usados[$key]['cantidad'] += $cantidad_usar;
                        } else {
                            $ingredientes_usados[$key] = [
                                'nombre' => $ing['nombre'],
                                'cantidad' => $cantidad_usar
                            ];
                        }
                    }
                }
                
                $conn->commit();
                
                // Mostrar mensaje de éxito con detalle de ingredientes descontados
                echo "<div class='alert alert-success'>
                        ✅ <strong>¡Venta procesada exitosamente!</strong><br>
                        📋 Venta #$venta_id por $".number_format($total, 2)."<br><br>
                        <strong>📦 Ingredientes descontados del stock:</strong><br>";
                
                foreach($ingredientes_usados as $ing_usado){
                    echo "• {$ing_usado['nombre']}: -{$ing_usado['cantidad']} unidades<br>";
                }
                
                echo "<br>
                      <a href='venta_detalle.php?id=$venta_id' class='btn btn-info mt-2'>👁️ Ver Detalle</a>
                      <a href='ventas.php' class='btn btn-success mt-2 ms-2'>📋 Ver Todas las Ventas</a>
                      <a href='productos.php' class='btn btn-warning mt-2 ms-2'>📦 Verificar Stock</a>
                      </div>";
                
            } catch (Exception $e) {
                $conn->rollback();
                echo "<div class='alert alert-danger'>❌ Error al procesar la venta: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<h2>➕ Nueva Venta</h2>

<div class="row">
    <!-- Formulario de venta -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">🛒 Carrito de Compras</h5>
            </div>
            <div class="card-body">
                <!-- Selección de cliente y empleado -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label"><strong>👤 Cliente</strong></label>
                        <select id="cliente_id" class="form-select" required>
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
                        <label class="form-label"><strong>👨‍💼 Empleado (Vendedor)</strong></label>
                        <select id="empleado_id" class="form-select" required>
                            <option value="">Seleccionar empleado...</option>
                            <?php
                            $empleados = $conn->query("SELECT id_empleado, nombre, apellido, cargo FROM empleados ORDER BY nombre");
                            while($e = $empleados->fetch_assoc()){
                                echo "<option value='{$e['id_empleado']}'>{$e['nombre']} {$e['apellido']} ({$e['cargo']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Selección de productos -->
                <div class="row mb-3">
                    <div class="col-md-7">
                        <label class="form-label"><strong>🍔 Hamburguesa</strong></label>
                        <select id="hamburguesa_select" class="form-select">
                            <option value="">Seleccionar hamburguesa...</option>
                            <?php
                            $hamburguesas = $conn->query("SELECT id_hamburguesa, nombre, precio FROM tipo_hamburguesa ORDER BY nombre");
                            while($h = $hamburguesas->fetch_assoc()){
                                // Calcular stock disponible para esta hamburguesa
                                $stock_sql = "SELECT MIN(FLOOR(p.stock / hp.cantidad)) as max_disponible
                                             FROM hamburguesa_producto hp
                                             JOIN productos p ON hp.producto_id = p.id_producto
                                             WHERE hp.hamburguesa_id = {$h['id_hamburguesa']}";
                                $stock_result = $conn->query($stock_sql);
                                $stock_info = $stock_result->fetch_assoc();
                                $max_disponible = $stock_info['max_disponible'] ?? 0;
                                
                                if($max_disponible > 0){
                                    $disponibilidad = " (máx: $max_disponible)";
                                    echo "<option value='{$h['id_hamburguesa']}' data-precio='{$h['precio']}' data-max='{$max_disponible}'>{$h['nombre']} - $".number_format($h['precio'], 2)."$disponibilidad</option>";
                                } else {
                                    echo "<option value='{$h['id_hamburguesa']}' data-precio='{$h['precio']}' data-max='0' disabled>{$h['nombre']} - $".number_format($h['precio'], 2)." (SIN STOCK)</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>Cantidad</strong></label>
                        <input type="number" id="cantidad_input" class="form-control" value="1" min="1" max="20">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button id="agregar_btn" class="btn btn-primary w-100">➕ Agregar</button>
                    </div>
                </div>

                <!-- Tabla del carrito -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Hamburguesa</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="carrito_tbody">
                            <tr id="carrito_vacio">
                                <td colspan="5" class="text-center text-muted py-4">
                                    🛒 El carrito está vacío. Agregue productos para continuar.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr id="carrito_total" style="display: none;">
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th class="text-end h5 text-success" id="total_display">$0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Botones de acción -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <button id="limpiar_carrito" class="btn btn-outline-danger w-100" disabled>
                            🗑️ Limpiar Carrito
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button id="procesar_venta" class="btn btn-success w-100 btn-lg" disabled>
                            💰 Procesar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de información -->
    <div class="col-md-4">
        <!-- Resumen rápido -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">📊 Resumen</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary" id="total_items">0</h4>
                        <small>Items</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success" id="total_resumen">$0.00</h4>
                        <small>Total</small>
                    </div>
                </div>
            </div>
        </div>

                <!-- Lista de hamburguesas disponibles -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">🍔 Menú Disponible</h6>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php
                $menu = $conn->query("SELECT id_hamburguesa, nombre, precio FROM tipo_hamburguesa ORDER BY precio");
                while($item = $menu->fetch_assoc()){
                    // Obtener ingredientes de esta hamburguesa
                    $ingredientes_sql = "SELECT p.nombre, hp.cantidad, p.stock 
                                        FROM hamburguesa_producto hp
                                        JOIN productos p ON hp.producto_id = p.id_producto
                                        WHERE hp.hamburguesa_id = {$item['id_hamburguesa']}
                                        ORDER BY p.nombre";
                    $ingredientes = $conn->query($ingredientes_sql);
                    
                    // Verificar disponibilidad
                    $disponible = true;
                    $stock_minimo = 999999;
                    $ingredientes_lista = [];
                    
                    while($ing = $ingredientes->fetch_assoc()){
                        $ingredientes_lista[] = $ing;
                        $hamburguesas_posibles = floor($ing['stock'] / $ing['cantidad']);
                        if($hamburguesas_posibles < $stock_minimo){
                            $stock_minimo = $hamburguesas_posibles;
                        }
                        if($hamburguesas_posibles == 0){
                            $disponible = false;
                        }
                    }
                    
                    $alert_class = '';
                    $status_text = '';
                    if(!$disponible){
                        $alert_class = 'border-danger bg-light';
                        $status_text = '<small class="text-danger">❌ Sin stock</small>';
                    } else if($stock_minimo <= 3){
                        $alert_class = 'border-warning';
                        $status_text = '<small class="text-warning">⚠️ Stock bajo ('.$stock_minimo.' disponibles)</small>';
                    } else {
                        $status_text = '<small class="text-success">✅ '.$stock_minimo.' disponibles</small>';
                    }
                    
                    echo "<div class='mb-3 p-3 border rounded $alert_class'>
                            <div class='d-flex justify-content-between align-items-start mb-2'>
                                <div>
                                    <strong>{$item['nombre']}</strong><br>
                                    <span class='text-success fw-bold'>$".number_format($item['precio'], 2)."</span><br>
                                    $status_text
                                </div>";
                    
                    if($disponible){
                        echo "<button class='btn btn-sm btn-outline-primary quick-add' data-id='{$item['id_hamburguesa']}' data-precio='{$item['precio']}' data-nombre='{$item['nombre']}'>
                                + Agregar
                              </button>";
                    } else {
                        echo "<button class='btn btn-sm btn-secondary disabled'>
                                Sin stock
                              </button>";
                    }
                    
                    echo "</div>";
                    
                    // Mostrar ingredientes
                    if(!empty($ingredientes_lista)){
                        echo "<div class='mt-2'>
                                <small class='text-muted fw-bold'>Ingredientes:</small><br>";
                        foreach($ingredientes_lista as $ing){
                            $color = $ing['stock'] <= 5 ? 'text-danger' : ($ing['stock'] <= 10 ? 'text-warning' : 'text-muted');
                            echo "<small class='$color'>• {$ing['nombre']} x{$ing['cantidad']} (stock: {$ing['stock']})</small><br>";
                        }
                        echo "</div>";
                    } else {
                        echo "<small class='text-warning'>⚠️ No tiene ingredientes configurados</small>";
                    }
                    
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Alerta de stock -->
        <div class="alert alert-info mt-3">
            <small>
                <strong>ℹ️ Leyenda:</strong><br>
                ✅ = Disponible | ⚠️ = Stock bajo | ❌ = Sin stock
            </small>
        </div>
    </div>
</div>

<!-- Formulario oculto para enviar la venta -->
<form id="venta_form" method="POST" style="display: none;">
    <input type="hidden" name="cliente_id" id="form_cliente_id">
    <input type="hidden" name="empleado_id" id="form_empleado_id">
    <input type="hidden" name="items_json" id="form_items_json">
    <input type="hidden" name="total" id="form_total">
    <input type="hidden" name="procesar_venta" value="1">
</form>

<div class="mt-3">
    <a href="ventas.php" class="btn btn-secondary">⬅️ Volver a Ventas</a>
</div>

<script>
// Variables globales
let carrito = [];
let total = 0;

// Elementos del DOM
const hamburguesaSelect = document.getElementById('hamburguesa_select');
const cantidadInput = document.getElementById('cantidad_input');
const agregarBtn = document.getElementById('agregar_btn');
const carritoTbody = document.getElementById('carrito_tbody');
const carritoVacio = document.getElementById('carrito_vacio');
const carritoTotal = document.getElementById('carrito_total');
const totalDisplay = document.getElementById('total_display');
const totalResumen = document.getElementById('total_resumen');
const totalItems = document.getElementById('total_items');
const limpiarBtn = document.getElementById('limpiar_carrito');
const procesarBtn = document.getElementById('procesar_venta');
const clienteSelect = document.getElementById('cliente_id');
const empleadoSelect = document.getElementById('empleado_id');

// Agregar producto al carrito
agregarBtn.addEventListener('click', function() {
    const selectedOption = hamburguesaSelect.options[hamburguesaSelect.selectedIndex];
    const hamburguesaId = parseInt(hamburguesaSelect.value);
    
    if(!hamburguesaId) {
        alert('Por favor selecciona una hamburguesa');
        return;
    }
    
    const cantidad = parseInt(cantidadInput.value) || 1;
    if(cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0');
        return;
    }
    
    const maxDisponible = parseInt(selectedOption.dataset.max) || 0;
    
    // Verificar si ya hay de este producto en el carrito
    const existingItem = carrito.find(item => item.id === hamburguesaId);
    const cantidadEnCarrito = existingItem ? existingItem.cantidad : 0;
    const cantidadTotal = cantidadEnCarrito + cantidad;
    
    if(cantidadTotal > maxDisponible) {
        alert(`❌ Stock insuficiente!\n\nDisponible: ${maxDisponible}\nEn carrito: ${cantidadEnCarrito}\nIntentando agregar: ${cantidad}\n\nMáximo que puedes agregar: ${maxDisponible - cantidadEnCarrito}`);
        return;
    }
    
    const nombre = selectedOption.text.split(' - 

// Actualizar vista del carrito
function actualizarCarrito() {
    carritoTbody.innerHTML = '';
    total = 0;
    let totalItemsCount = 0;
    
    if(carrito.length === 0) {
        carritoTbody.appendChild(carritoVacio.cloneNode(true));
        carritoTotal.style.display = 'none';
        limpiarBtn.disabled = true;
        procesarBtn.disabled = true;
    } else {
        carrito.forEach((item, index) => {
            total += item.subtotal;
            totalItemsCount += item.cantidad;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.nombre}</strong></td>
                <td class="text-center">
                    <span class="badge bg-primary">${item.cantidad}</span>
                </td>
                <td class="text-end">$${item.precio.toFixed(2)}</td>
                <td class="text-end"><strong>$${item.subtotal.toFixed(2)}</strong></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarItem(${index})">🗑️</button>
                </td>
            `;
            carritoTbody.appendChild(row);
        });
        
        carritoTotal.style.display = 'table-row';
        limpiarBtn.disabled = false;
        procesarBtn.disabled = false;
    }
    
    totalDisplay.textContent = '$' + total.toFixed(2);
    totalResumen.textContent = '$' + total.toFixed(2);
    totalItems.textContent = totalItemsCount;
}

// Eliminar item del carrito
function eliminarItem(index) {
    carrito.splice(index, 1);
    actualizarCarrito();
}

// Limpiar carrito
limpiarBtn.addEventListener('click', function() {
    if(confirm('¿Limpiar todo el carrito?')) {
        carrito = [];
        actualizarCarrito();
    }
});

// Procesar venta
procesarBtn.addEventListener('click', function() {
    const clienteId = clienteSelect.value;
    const empleadoId = empleadoSelect.value;
    
    if(!clienteId) {
        alert('Por favor selecciona un cliente');
        clienteSelect.focus();
        return;
    }
    
    if(!empleadoId) {
        alert('Por favor selecciona un empleado');
        empleadoSelect.focus();
        return;
    }
    
    if(carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }
    
    if(confirm(`¿Procesar venta por $${total.toFixed(2)}?`)) {
        document.getElementById('form_cliente_id').value = clienteId;
        document.getElementById('form_empleado_id').value = empleadoId;
        document.getElementById('form_items_json').value = JSON.stringify(carrito);
        document.getElementById('form_total').value = total.toFixed(2);
        
        document.getElementById('venta_form').submit();
    }
});

// Inicialización
actualizarCarrito();
</script>

<?php include("footer.php"); ?>)[0].split(' (máx:')[0];
    const precio = parseFloat(selectedOption.dataset.precio);
    const subtotal = precio * cantidad;

    // Verificar si ya existe en el carrito
    const existingIndex = carrito.findIndex(item => item.id === hamburguesaId);
    if(existingIndex >= 0){
        carrito[existingIndex].cantidad += cantidad;
        carrito[existingIndex].subtotal = carrito[existingIndex].cantidad * carrito[existingIndex].precio;
    } else {
        carrito.push({
            id: hamburguesaId,
            nombre: nombre,
            precio: precio,
            cantidad: cantidad,
            subtotal: subtotal,
            maxDisponible: maxDisponible
        });
    }
    
    actualizarCarrito();
    hamburguesaSelect.value = '';
    cantidadInput.value = 1;
});

// Botones de agregar rápido
document.querySelectorAll('.quick-add').forEach(btn => {
    btn.addEventListener('click', function() {
        const hamburguesaId = parseInt(this.dataset.id);
        const precio = parseFloat(this.dataset.precio);
        const nombre = this.dataset.nombre;
        
        // Obtener stock disponible (necesitamos hacer una consulta AJAX para esto)
        // Por simplicidad, vamos a permitir agregar 1 y validar en el servidor
        const existingIndex = carrito.findIndex(item => item.id === hamburguesaId);
        if(existingIndex >= 0){
            carrito[existingIndex].cantidad += 1;
            carrito[existingIndex].subtotal = carrito[existingIndex].cantidad * carrito[existingIndex].precio;
        } else {
            carrito.push({
                id: hamburguesaId,
                nombre: nombre,
                precio: precio,
                cantidad: 1,
                subtotal: precio,
                maxDisponible: 999 // Se validará en el servidor
            });
        }
        
        actualizarCarrito();
    });
});

// Actualizar vista del carrito
function actualizarCarrito() {
    carritoTbody.innerHTML = '';
    total = 0;
    let totalItemsCount = 0;
    
    if(carrito.length === 0) {
        carritoTbody.appendChild(carritoVacio.cloneNode(true));
        carritoTotal.style.display = 'none';
        limpiarBtn.disabled = true;
        procesarBtn.disabled = true;
    } else {
        carrito.forEach((item, index) => {
            total += item.subtotal;
            totalItemsCount += item.cantidad;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.nombre}</strong></td>
                <td class="text-center">
                    <span class="badge bg-primary">${item.cantidad}</span>
                </td>
                <td class="text-end">$${item.precio.toFixed(2)}</td>
                <td class="text-end"><strong>$${item.subtotal.toFixed(2)}</strong></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarItem(${index})">🗑️</button>
                </td>
            `;
            carritoTbody.appendChild(row);
        });
        
        carritoTotal.style.display = 'table-row';
        limpiarBtn.disabled = false;
        procesarBtn.disabled = false;
    }
    
    totalDisplay.textContent = '$' + total.toFixed(2);
    totalResumen.textContent = '$' + total.toFixed(2);
    totalItems.textContent = totalItemsCount;
}

// Eliminar item del carrito
function eliminarItem(index) {
    carrito.splice(index, 1);
    actualizarCarrito();
}

// Limpiar carrito
limpiarBtn.addEventListener('click', function() {
    if(confirm('¿Limpiar todo el carrito?')) {
        carrito = [];
        actualizarCarrito();
    }
});

// Procesar venta
procesarBtn.addEventListener('click', function() {
    const clienteId = clienteSelect.value;
    const empleadoId = empleadoSelect.value;
    
    if(!clienteId) {
        alert('Por favor selecciona un cliente');
        clienteSelect.focus();
        return;
    }
    
    if(!empleadoId) {
        alert('Por favor selecciona un empleado');
        empleadoSelect.focus();
        return;
    }
    
    if(carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }
    
    if(confirm(`¿Procesar venta por $${total.toFixed(2)}?`)) {
        document.getElementById('form_cliente_id').value = clienteId;
        document.getElementById('form_empleado_id').value = empleadoId;
        document.getElementById('form_items_json').value = JSON.stringify(carrito);
        document.getElementById('form_total').value = total.toFixed(2);
        
        document.getElementById('venta_form').submit();
    }
});

// Inicialización
actualizarCarrito();
</script>

<?php include("footer.php"); ?>