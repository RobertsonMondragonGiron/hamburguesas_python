<?php
include("config/db.php");
include("header.php");

$hamburguesa_id = (int)$_GET['id'];
$hamburguesa_result = $conn->query("SELECT nombre FROM tipo_hamburguesa WHERE id_hamburguesa = $hamburguesa_id");
$hamburguesa = $hamburguesa_result->fetch_assoc();
?>

<h2>🍞 Ingredientes - <?= $hamburguesa['nombre'] ?></h2>

<?php
if(isset($_POST['agregar_ingrediente'])){
    $producto_id = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];
    
    // Verificar si este ingrediente ya está en esta hamburguesa
    $check = $conn->query("SELECT id, cantidad FROM hamburguesa_producto WHERE hamburguesa_id = $hamburguesa_id AND producto_id = $producto_id");
    
    if($check->num_rows > 0){
        // Ya existe: actualizar la cantidad (SUMAR al existente)
        $existing = $check->fetch_assoc();
        $nueva_cantidad = $existing['cantidad'] + $cantidad;
        
        $stmt = $conn->prepare("UPDATE hamburguesa_producto SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva_cantidad, $existing['id']);
        $stmt->execute();
        
        echo "<div class='alert alert-info'>
                ℹ️ <strong>Stock actualizado!</strong><br>
                Se agregaron <strong>$cantidad unidades</strong> al ingrediente existente.<br>
                Cantidad anterior: <strong>{$existing['cantidad']}</strong> → Nueva cantidad: <strong>$nueva_cantidad</strong>
              </div>";
    } else {
        // No existe: agregarlo como nuevo ingrediente
        $stmt = $conn->prepare("INSERT INTO hamburguesa_producto (hamburguesa_id, producto_id, cantidad) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $hamburguesa_id, $producto_id, $cantidad);
        $stmt->execute();
        
        echo "<div class='alert alert-success'>✅ <strong>Nuevo ingrediente agregado</strong> con $cantidad unidades.</div>";
    }
}

// Reabastecer stock de productos
if(isset($_POST['reabastecer_stock'])){
    $producto_id = (int)$_POST['producto_id_stock'];
    $cantidad_restock = (int)$_POST['cantidad_restock'];
    
    if($cantidad_restock > 0){
        // Obtener stock actual
        $current_stock = $conn->query("SELECT nombre, stock FROM productos WHERE id_producto = $producto_id")->fetch_assoc();
        $nuevo_stock = $current_stock['stock'] + $cantidad_restock;
        
        // Actualizar stock
        $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id_producto = ?");
        $stmt->bind_param("ii", $nuevo_stock, $producto_id);
        $stmt->execute();
        
        echo "<div class='alert alert-success'>
                📦 <strong>Stock reabastecido!</strong><br>
                Producto: <strong>{$current_stock['nombre']}</strong><br>
                Stock anterior: <strong>{$current_stock['stock']}</strong> → Nuevo stock: <strong>$nuevo_stock</strong>
              </div>";
    }
}

if(isset($_GET['eliminar_ingrediente'])){
    $id = (int)$_GET['eliminar_ingrediente'];
    $conn->query("DELETE FROM hamburguesa_producto WHERE id = $id");
    echo "<div class='alert alert-success'>✅ Ingrediente eliminado.</div>";
}
?>

<!-- SECCIÓN 1: Agregar/Actualizar Ingredientes -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🍞 Agregar Ingrediente a la Hamburguesa</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Producto</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">-- Seleccionar Ingrediente --</option>
                                <?php
                                $productos = $conn->query("SELECT p.id_producto, p.nombre, p.stock 
                                                          FROM productos p 
                                                          ORDER BY p.nombre");
                                while($p = $productos->fetch_assoc()){
                                    $stock_warning = $p['stock'] <= 10 ? ' ⚠️ (Stock bajo)' : '';
                                    echo "<option value='{$p['id_producto']}'>
                                            {$p['nombre']} (Stock: {$p['stock']})$stock_warning
                                          </option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cantidad por Hamburguesa</label>
                            <input type="number" name="cantidad" class="form-control" value="1" min="1" max="10" required>
                        </div>
                        <div class="col-md-3">
                            <button name="agregar_ingrediente" class="btn btn-success w-100">
                                ➕ Agregar/Actualizar
                            </button>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        💡 <strong>Tip:</strong> Si el ingrediente ya existe, se sumará la cantidad a la existente.
                    </small>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SECCIÓN 2: Reabastecer Stock -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">📦 Reabastecer Stock</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select name="producto_id_stock" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php
                            $productos_stock = $conn->query("SELECT id_producto, nombre, stock FROM productos ORDER BY stock ASC, nombre");
                            while($p = $productos_stock->fetch_assoc()){
                                $urgente = $p['stock'] <= 5 ? ' 🔴 ¡URGENTE!' : ($p['stock'] <= 10 ? ' ⚠️ Bajo' : '');
                                echo "<option value='{$p['id_producto']}'>
                                        {$p['nombre']} ({$p['stock']})$urgente
                                      </option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad a Agregar</label>
                        <input type="number" name="cantidad_restock" class="form-control" min="1" value="50" required>
                    </div>
                    <button name="reabastecer_stock" class="btn btn-warning w-100">
                        📦 Reabastecer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN 3: Ingredientes Actuales -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">🍔 Ingredientes de "<?= $hamburguesa['nombre'] ?>"</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Ingrediente</th>
                        <th>Cantidad por Hamburguesa</th>
                        <th>Stock Disponible</th>
                        <th>Estado Stock</th>
                        <th>Hamburguesas Posibles</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT hp.id, hp.cantidad, p.nombre, p.stock, p.id_producto
                            FROM hamburguesa_producto hp
                            JOIN productos p ON hp.producto_id = p.id_producto
                            WHERE hp.hamburguesa_id = $hamburguesa_id
                            ORDER BY p.nombre";
                    $result = $conn->query($sql);
                    
                    while($row = $result->fetch_assoc()){
                        // Calcular cuántas hamburguesas se pueden hacer con este ingrediente
                        $hamburguesas_posibles = floor($row['stock'] / $row['cantidad']);
                        
                        // Determinar el estado del stock
                        if($row['stock'] <= 5){
                            $stock_badge = "<span class='badge bg-danger'>🔴 Crítico</span>";
                            $stock_class = 'text-danger fw-bold';
                        } else if($row['stock'] <= 10){
                            $stock_badge = "<span class='badge bg-warning text-dark'>⚠️ Bajo</span>";
                            $stock_class = 'text-warning fw-bold';
                        } else if($row['stock'] <= 20){
                            $stock_badge = "<span class='badge bg-info'>ℹ️ Medio</span>";
                            $stock_class = 'text-info';
                        } else {
                            $stock_badge = "<span class='badge bg-success'>✅ Bueno</span>";
                            $stock_class = 'text-success';
                        }
                        
                        echo "<tr>
                                <td><strong>{$row['nombre']}</strong></td>
                                <td><span class='badge bg-primary'>{$row['cantidad']}</span></td>
                                <td class='$stock_class'>{$row['stock']}</td>
                                <td>$stock_badge</td>
                                <td><strong>$hamburguesas_posibles</strong> hamburguesas</td>
                                <td>
                                    <a href='hamburguesa_productos.php?id=$hamburguesa_id&eliminar_ingrediente={$row['id']}' 
                                       class='btn btn-sm btn-danger' 
                                       onclick=\"return confirm('¿Eliminar {$row['nombre']} de esta hamburguesa?')\">
                                       🗑️ Quitar
                                    </a>
                                </td>
                              </tr>";
                    }
                    
                    if($result->num_rows == 0){
                        echo "<tr><td colspan='6' class='text-center text-muted'>No hay ingredientes agregados a esta hamburguesa.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="tipo_hamburguesa.php" class="btn btn-secondary">⬅️ Volver a Hamburguesas</a>
    <a href="productos.php" class="btn btn-info">📦 Gestionar Productos</a>
</div>

<?php include("footer.php"); ?>