<?php 
include("config/db.php");
include("header.php");
?>

<h2>📦 Ingrediente</h2>

<?php

if(isset($_GET['eliminar'])){
    $id = (int)$_GET['eliminar'];
    
  
    $check_hamburguesas = $conn->query("SELECT COUNT(*) as total FROM hamburguesa_producto WHERE producto_id = $id");
    $uso_hamburguesas = $check_hamburguesas->fetch_assoc()['total'];
    
    if($uso_hamburguesas > 0){
        echo "<div class='alert alert-danger'>
                ❌ No se puede eliminar el producto porque está siendo usado en $uso_hamburguesas hamburguesa(s).
                <br>Primero debe quitarlo de todas las hamburguesas.
              </div>";
    } else {
        $conn->query("DELETE FROM productos WHERE id_producto = $id");
        echo "<div class='alert alert-success'>✅ Producto eliminado correctamente.</div>";
        echo "<script>setTimeout(() => window.location='productos.php', 1500);</script>";
    }
}
?>

<a href="nuevo_producto.php" class="btn btn-success mb-3">➕ Nuevo Ingrediente</a>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Usado en Hamburguesas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.*, COUNT(hp.id) as usado_en_hamburguesas
                            FROM productos p
                            LEFT JOIN hamburguesa_producto hp ON p.id_producto = hp.producto_id
                            GROUP BY p.id_producto
                            ORDER BY p.stock ASC, p.nombre";
                    $result = $conn->query($sql);
                    
                    while($row = $result->fetch_assoc()){
                        // Determinar estado del stock
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
                        
                        $usado_en = $row['usado_en_hamburguesas'] > 0 ? $row['usado_en_hamburguesas'] : 'Ninguna';
                        $puede_eliminar = $row['usado_en_hamburguesas'] == 0;
                        
                        echo "<tr>
                                <td>{$row['id_producto']}</td>
                                <td><strong>{$row['nombre']}</strong></td>
                                <td class='$stock_class'>{$row['stock']}</td>
                                <td>$stock_badge</td>
                                <td><span class='badge bg-secondary'>$usado_en</span></td>
                                <td>";
                        
                        echo "<a href='editar_producto.php?id={$row['id_producto']}' class='btn btn-sm btn-primary me-1'>✏️ Editar</a>";
                        
                        if($puede_eliminar){
                            echo "<a href='productos.php?eliminar={$row['id_producto']}' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Eliminar producto {$row['nombre']}?')\">🗑️ Eliminar</a>";
                        } else {
                            echo "<button class='btn btn-sm btn-secondary disabled' title='No se puede eliminar: está en uso'>🔒 Protegido</button>";
                        }
                        
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="row mt-4">
    <div class="col-md-12">
        <div class="alert alert-info">
            <h5>📊 Leyenda de Estados</h5>
            <div class="row">
                <div class="col-md-3">
                    <span class='badge bg-danger'>🔴 Crítico</span> Stock ≤ 5 unidades
                </div>
                <div class="col-md-3">
                    <span class='badge bg-warning text-dark'>⚠️ Bajo</span> Stock ≤ 10 unidades
                </div>
                <div class="col-md-3">
                    <span class='badge bg-info'>ℹ️ Medio</span> Stock ≤ 20 unidades
                </div>
                <div class="col-md-3">
                    <span class='badge bg-success'>✅ Bueno</span> Stock > 20 unidades
                </div>
            </div>
        </div>
    </div>
</div>

<a href="index.php" class="btn btn-secondary">⬅️ Volver</a>

<?php include("footer.php"); ?>