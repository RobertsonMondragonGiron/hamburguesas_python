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
    
    
    $check = $conn->query("SELECT id FROM hamburguesa_producto WHERE hamburguesa_id = $hamburguesa_id AND producto_id = $producto_id");
    if($check->num_rows > 0){
        echo "<div class='alert alert-warning'>⚠️ Este ingrediente ya está agregado.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO hamburguesa_producto (hamburguesa_id, producto_id, cantidad) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $hamburguesa_id, $producto_id, $cantidad);
        $stmt->execute();
        echo "<div class='alert alert-success'>✅ Ingrediente agregado.</div>";
    }
}

if(isset($_GET['eliminar_ingrediente'])){
    $id = (int)$_GET['eliminar_ingrediente'];
    $conn->query("DELETE FROM hamburguesa_producto WHERE id = $id");
    echo "<div class='alert alert-success'>✅ Ingrediente eliminado.</div>";
}
?>

<form method="POST" class="card p-3 mb-4">
    <h5>Agregar Ingrediente</h5>
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label>Producto</label>
            <select name="producto_id" class="form-select" required>
                <option value="">-- Seleccionar --</option>
                <?php
                $productos = $conn->query("SELECT id_producto, nombre FROM productos ORDER BY nombre");
                while($p = $productos->fetch_assoc()){
                    echo "<option value='{$p['id_producto']}'>{$p['nombre']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Cantidad</label>
            <input type="number" name="cantidad" class="form-control" value="1" min="1" required>
        </div>
        <div class="col-md-3">
            <button name="agregar_ingrediente" class="btn btn-success w-100">Agregar</button>
        </div>
    </div>
</form>

<h5>Ingredientes Actuales</h5>
<table class="table table-striped bg-white">
    <thead class="table-secondary">
        <tr><th>Ingrediente</th><th>Cantidad</th><th>Stock Disponible</th><th>Acciones</th></tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT hp.id, hp.cantidad, p.nombre, p.stock 
                FROM hamburguesa_producto hp
                JOIN productos p ON hp.producto_id = p.id_producto
                WHERE hp.hamburguesa_id = $hamburguesa_id
                ORDER BY p.nombre";
        $result = $conn->query($sql);
        
        while($row = $result->fetch_assoc()){
            $stock_class = $row['stock'] <= 10 ? 'text-danger' : '';
            echo "<tr>
                    <td>{$row['nombre']}</td>
                    <td>{$row['cantidad']}</td>
                    <td class='$stock_class'>{$row['stock']}</td>
                    <td>
                        <a href='hamburguesa_productos.php?id=$hamburguesa_id&eliminar_ingrediente={$row['id']}' 
                           class='btn btn-sm btn-danger' 
                           onclick=\"return confirm('¿Eliminar ingrediente?')\">🗑️ Eliminar</a>
                    </td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<a href="tipo_hamburguesa.php" class="btn btn-secondary">⬅️ Volver a Hamburguesas</a>

<?php include("footer.php"); ?>