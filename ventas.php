<?php 
include("config/db.php");
include("header.php");
?>

<h2>📋 Ventas</h2>

<?php
// Estadísticas rápidas
$stats_hoy = $conn->query("SELECT COUNT(*) as ventas_hoy, COALESCE(SUM(valor_total), 0) as ingresos_hoy 
                          FROM ventas WHERE DATE(fecha) = CURDATE()")->fetch_assoc();

$stats_mes = $conn->query("SELECT COUNT(*) as ventas_mes, COALESCE(SUM(valor_total), 0) as ingresos_mes 
                          FROM ventas WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())")->fetch_assoc();
?>

<!-- Panel de estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>📊 Ventas Hoy</h6>
                <h4><?= $stats_hoy['ventas_hoy'] ?></h4>
                <small>$<?= number_format($stats_hoy['ingresos_hoy'], 2) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>📈 Ventas del Mes</h6>
                <h4><?= $stats_mes['ventas_mes'] ?></h4>
                <small>$<?= number_format($stats_mes['ingresos_mes'], 2) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>💰 Promedio por Venta</h6>
                <h4>$<?= $stats_mes['ventas_mes'] > 0 ? number_format($stats_mes['ingresos_mes'] / $stats_mes['ventas_mes'], 2) : '0.00' ?></h4>
                <small>Mes actual</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <a href="nueva_venta.php" class="btn btn-dark btn-lg w-100">
                    ➕ Nueva Venta
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente" class="form-select">
                    <option value="">Todos los clientes</option>
                    <?php
                    $clientes = $conn->query("SELECT DISTINCT c.id_cliente, c.nombre 
                                             FROM clientes c 
                                             INNER JOIN ventas v ON c.id_cliente = v.cliente_id 
                                             ORDER BY c.nombre");
                    while($c = $clientes->fetch_assoc()){
                        $selected = (isset($_GET['cliente']) && $_GET['cliente'] == $c['id_cliente']) ? 'selected' : '';
                        echo "<option value='{$c['id_cliente']}' $selected>{$c['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Empleado</label>
                <select name="empleado" class="form-select">
                    <option value="">Todos los empleados</option>
                    <?php
                    $empleados = $conn->query("SELECT DISTINCT e.id_empleado, e.nombre 
                                              FROM empleados e 
                                              INNER JOIN ventas v ON e.id_empleado = v.empleado_id 
                                              ORDER BY e.nombre");
                    while($e = $empleados->fetch_assoc()){
                        $selected = (isset($_GET['empleado']) && $_GET['empleado'] == $e['id_empleado']) ? 'selected' : '';
                        echo "<option value='{$e['id_empleado']}' $selected>{$e['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?= $_GET['fecha_desde'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?= $_GET['fecha_hasta'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">🔍 Filtrar</button>
                <a href="ventas.php" class="btn btn-outline-secondary w-100 mt-1">🔄 Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de ventas -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">📋 Lista de Ventas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-secondary">
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Total</th>
                        <th>Items</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Construir la consulta con filtros
                    $where_conditions = [];
                    $params = [];
                    $types = '';
                    
                    if(isset($_GET['cliente']) && !empty($_GET['cliente'])){
                        $where_conditions[] = "v.cliente_id = ?";
                        $params[] = (int)$_GET['cliente'];
                        $types .= 'i';
                    }
                    
                    if(isset($_GET['empleado']) && !empty($_GET['empleado'])){
                        $where_conditions[] = "v.empleado_id = ?";
                        $params[] = (int)$_GET['empleado'];
                        $types .= 'i';
                    }
                    
                    if(isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])){
                        $where_conditions[] = "DATE(v.fecha) >= ?";
                        $params[] = $_GET['fecha_desde'];
                        $types .= 's';
                    }
                    
                    if(isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])){
                        $where_conditions[] = "DATE(v.fecha) <= ?";
                        $params[] = $_GET['fecha_hasta'];
                        $types .= 's';
                    }
                    
                    $where_clause = '';
                    if(!empty($where_conditions)){
                        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
                    }
                    
                    $sql = "SELECT v.id_venta, v.cliente_id, v.empleado_id, v.fecha, v.valor_total, 
                                   c.nombre AS cliente, e.nombre AS empleado,
                                   COUNT(dv.id_detalle) as total_items,
                                   SUM(dv.cantidad) as total_productos
                            FROM ventas v
                            LEFT JOIN clientes c ON v.cliente_id = c.id_cliente
                            LEFT JOIN empleados e ON v.empleado_id = e.id_empleado
                            LEFT JOIN detalle_ventas dv ON v.id_venta = dv.venta_id
                            $where_clause
                            GROUP BY v.id_venta
                            ORDER BY v.id_venta DESC
                            LIMIT 50";
                    
                    if(!empty($params)){
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query($sql);
                    }
                    
                    $total_mostrado = 0;
                    $suma_total = 0;
                    
                    while($row = $result->fetch_assoc()){
                        $total_mostrado++;
                        $suma_total += $row['valor_total'];
                        
                        // Determinar el color según el monto
                        if($row['valor_total'] >= 50000){
                            $total_class = 'text-success fw-bold';
                        } else if($row['valor_total'] >= 20000){
                            $total_class = 'text-primary fw-bold';
                        } else {
                            $total_class = '';
                        }
                        
                        echo "<tr>
                                <td><span class='badge bg-secondary'>#{$row['id_venta']}</span></td>
                                <td><strong>{$row['cliente']}</strong></td>
                                <td>{$row['empleado']}</td>
                                <td>".date('d/m/Y', strtotime($row['fecha']))."</td>
                                <td>".date('H:i', strtotime($row['fecha']))."</td>
                                <td class='$total_class'>$".number_format($row['valor_total'], 2)."</td>
                                <td><span class='badge bg-info'>{$row['total_productos']} productos</span></td>
                                <td>
                                    <a href='venta_detalle.php?id={$row['id_venta']}' class='btn btn-sm btn-info'>
                                        👁️ Ver Detalle
                                    </a>
                                </td>
                              </tr>";
                    }
                    
                    if($total_mostrado == 0){
                        echo "<tr><td colspan='8' class='text-center text-muted py-4'>
                                📭 No se encontraron ventas con los filtros seleccionados.
                              </td></tr>";
                    }
                    ?>
                </tbody>
                <?php if($total_mostrado > 0): ?>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">TOTAL (<?= $total_mostrado ?> ventas):</td>
                        <td class="text-success">$<?= number_format($suma_total, 2) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if($result->num_rows >= 50): ?>
        <div class="alert alert-info mt-3">
            ℹ️ Mostrando las últimas 50 ventas. Use los filtros para buscar ventas específicas.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="d-grid gap-2">
            <a href="nueva_venta.php" class="btn btn-success btn-lg">
                ➕ Registrar Nueva Venta
            </a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-grid gap-2">
            <a href="reporte_ventas.php" class="btn btn-info btn-lg">
                📊 Ver Reportes Detallados
            </a>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="index.php" class="btn btn-secondary">⬅️ Volver al Inicio</a>
</div>

<?php include("footer.php"); ?>