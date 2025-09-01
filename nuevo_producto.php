<?php 
include("config/db.php");
include("header.php");
?>

<h2>➕ Nuevo Producto</h2>

<?php
if(isset($_POST['guardar'])){
    $nombre = trim($_POST['nombre']);
    $stock = (int)$_POST['stock'];
    
    // Verificar que el nombre no esté vacío
    if(empty($nombre)){
        echo "<div class='alert alert-danger'>❌ El nombre del producto es obligatorio.</div>";
    } else {
        // Verificar si el producto ya existe
        $check = $conn->prepare("SELECT id_producto FROM productos WHERE LOWER(nombre) = LOWER(?)");
        $check->bind_param("s", $nombre);
        $check->execute();
        $result = $check->get_result();
        
        if($result->num_rows > 0){
            echo "<div class='alert alert-warning'>⚠️ Ya existe un producto con ese nombre.</div>";
        } else {
            // Insertar el nuevo producto
            $stmt = $conn->prepare("INSERT INTO productos (nombre, stock) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $stock);
            
            if($stmt->execute()){
                echo "<div class='alert alert-success'>
                        ✅ Producto <strong>$nombre</strong> registrado correctamente con $stock unidades.
                      </div>";
                echo "<script>setTimeout(() => window.location='productos.php', 2000);</script>";
            } else {
                echo "<div class='alert alert-danger'>❌ Error al guardar el producto.</div>";
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">📦 Información del Producto</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <strong>Nombre del Producto</strong>
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="nombre" 
                               class="form-control" 
                               placeholder="Ej: Pan, Carne, Queso, Lechuga..." 
                               required 
                               maxlength="50">
                        <small class="text-muted">Máximo 50 caracteres</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <strong>Stock Inicial</strong>
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               name="stock" 
                               class="form-control" 
                               min="0" 
                               max="9999" 
                               value="50" 
                               required>
                        <small class="text-muted">Cantidad de unidades disponibles</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button name="guardar" class="btn btn-success btn-lg">
                            ✅ Guardar Producto
                        </button>
                        <a href="productos.php" class="btn btn-outline-secondary">
                            ⬅️ Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Panel de ayuda -->
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-header">
                <h6 class="mb-0">💡 Consejos para Productos</h6>
            </div>
            <div class="card-body">
                <h6>Ejemplos de productos comunes:</h6>
                <ul class="mb-3">
                    <li><strong>Pan:</strong> Para todas las hamburguesas</li>
                    <li><strong>Carne:</strong> Proteína principal</li>
                    <li><strong>Queso:</strong> Para hamburguesas especiales</li>
                    <li><strong>Lechuga:</strong> Verdura fresca</li>
                    <li><strong>Tomate:</strong> Verdura adicional</li>
                    <li><strong>Cebolla:</strong> Condimento</li>
                    <li><strong>Papas Fritas:</strong> Acompañamiento</li>
                </ul>
                
                <h6>Stock recomendado:</h6>
                <ul>
                    <li><strong>Productos básicos:</strong> 100+ unidades</li>
                    <li><strong>Productos especiales:</strong> 50+ unidades</li>
                    <li><strong>Condimentos:</strong> 30+ unidades</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>