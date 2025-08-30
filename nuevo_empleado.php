<?php include("config/db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <?php include("header.php"); ?>

<h2>➕ Registrar Empleado</h2>

<?php include("footer.php"); ?>
    
    <form method="POST" class="card p-4 shadow">
        <div class="mb-3">
            <label>Nombre:</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Apellido:</label>
            <input type="text" name="apellido" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Cargo:</label>
            <input type="text" name="cargo" class="form-control" required>
        </div>
        <button type="submit" name="guardar" class="btn btn-success">Guardar</button>
        <a href="empleados.php" class="btn btn-secondary">Cancelar</a>
    </form>

    <?php
    if(isset($_POST['guardar'])){
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $cargo = $_POST['cargo'];

        $conn->query("INSERT INTO empleados (nombre, apellido, cargo) VALUES ('$nombre','$apellido','$cargo')");
        echo "<div class='alert alert-success mt-3'>✅ Empleado registrado.</div>";
    }
    ?>
</div>
</body>
</html>
