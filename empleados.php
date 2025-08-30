<?php include("config/db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
<?php include("header.php"); ?>

<h2>👤 Empleados</h2>

<?php include("footer.php"); ?>

    <a href="nuevo_empleado.php" class="btn btn-success mb-3">➕ Nuevo Empleado</a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Cargo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT * FROM empleados";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
            echo "<tr>
                    <td>".$row['id_empleado']."</td>
                    <td>".$row['nombre']."</td>
                    <td>".$row['apellido']."</td>
                    <td>".$row['cargo']."</td>
                    <td>
                        <a href='editar_empleado.php?id=".$row['id_empleado']."' class='btn btn-sm btn-primary'>✏️ Editar</a>
                        <a href='empleados.php?eliminar=".$row['id_empleado']."' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Eliminar empleadp?')\">🗑️ Eliminar</a>
                    </td>
                  </tr>";
        }
        ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">⬅️ Volver</a>
</div>

<?php
// Eliminar cliente
if(isset($_GET['eliminar'])){
    $id = $_GET['eliminar'];
    $conn->query("DELETE FROM empleados WHERE id_empleado=$id");
    header("Location: empleados.php");
}
?>
</body>
</html>