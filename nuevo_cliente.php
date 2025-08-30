<?php include("config/db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Cliente</title>
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
    <?php include("header.php"); ?>

<h2>➕ Registrar Cliente</h2>

<?php include("footer.php"); ?>

    
    <form method="POST">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Dirección:</label><br>
        <input type="text" name="direccion"><br><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono"><br><br>

        <input type="submit" name="guardar" value="Guardar">
    </form>

    <?php
    if(isset($_POST['guardar'])){
        $nombre = $_POST['nombre'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];

        $conn->query("INSERT INTO clientes (nombre, direccion, telefono) VALUES ('$nombre','$direccion','$telefono')");

        echo "<p style='color:green;'>✅ Cliente registrado.</p>";
    }
    ?>
    <br>
    <a href="clientes.php">⬅️ Volver</a>
</body>
</html>
