
<?php 
include("config/db.php");
include("header.php");

// Filtros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
?>

<h2>📊 Reporte de Ventas</h2>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label>Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
            </div>
            <div class="col-md-4">
                <label>Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">🔍 Filtrar</button>
            </div>
        </form>
    </div>
</div>

<?php
// Estadísticas generales
$stats_sql = "SELECT 
                COUNT(*) as total_ventas,
                SUM(valor_total) as ingresos_total,
                AVG(valor_total) as promedio_venta
              FROM ventas 
              WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
$stats = $conn->query($stats_sql)->fetch_assoc();

// Ventas por empleado
$empleados_sql = "SELECT 
                    e.nombre,
                    COUNT(v.id_venta) as num_ventas,
                    COALESCE(SUM(v.valor_total), 0) as total_ventas
                  FROM empleados e
                  LEFT JOIN ventas v ON e.id_empleado = v.empleado_id 
                  AND DATE(v.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                  GROUP BY e.id_empleado, e.nombre
                  ORDER BY total_ventas DESC";
$empleados = $conn->query($empleados_sql);

// Hamburguesas más vendidas
$hamburguesas_sql = "SELECT 
                        th.nombre,
                        SUM(dv.cantidad) as cantidad_vendida,
                        SUM(dv.subtotal) as total_ingresos
                     FROM detalle_ventas dv
                     JOIN tipo_hamburguesa th ON dv.hamburguesa_id = th.id_hamburguesa
                     JOIN ventas v ON dv.venta_id = v.id_venta
                     WHERE DATE(v.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                     GROUP BY th.id_hamburguesa, th.nombre
                     ORDER BY cantidad_vendida DESC";
$hamburguesas = $conn->query($hamburguesas_sql);
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5>Total Ventas</h5>
                <h2><?= $stats['total_ventas'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5>Ingresos Totales</h5>
                <h2>$<?= number_format($stats['ingresos_total'], 2) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5>Promedio por Venta</h5>
                <h2>$<?= number_format($stats['promedio_venta'], 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Ventas por Empleado</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Empleado</th><th>Ventas</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php while($emp = $empleados->fetch_assoc()): ?>
                        <tr>
                            <td><?= $emp['nombre'] ?></td>
                            <td><?= $emp['num_ventas'] ?></td>
                            <td>$<?= number_format($emp['total_ventas'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Hamburguesas Más Vendidas</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Hamburguesa</th><th>Cantidad</th><th>Ingresos</th></tr>
                    </thead>
                    <tbody>
                        <?php while($hamb = $hamburguesas->fetch_assoc()): ?>
                        <tr>
                            <td><?= $hamb['nombre'] ?></td>
                            <td><?= $hamb['cantidad_vendida'] ?></td>
                            <td>$<?= number_format($hamb['total_ingresos'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>