<?php include("config/db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
<?php include("header.php"); ?>

<h2>👤 Clientes</h2>

<?php include("footer.php"); ?>

    <a href="nuevo_cliente.php" class="btn btn-success mb-3">➕ Nuevo Cliente</a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT * FROM clientes";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
            echo "<tr>
                    <td>".$row['id_cliente']."</td>
                    <td>".$row['nombre']."</td>
                    <td>".$row['direccion']."</td>
                    <td>".$row['telefono']."</td>
                    <td>
                        <a href='editar_cliente.php?id=".$row['id_cliente']."' class='btn btn-sm btn-primary'>✏️ Editar</a>
                        <a href='clientes.php?eliminar=".$row['id_cliente']."' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Eliminar cliente?')\">🗑️ Eliminar</a>
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
    $conn->query("DELETE FROM clientes WHERE id_cliente=$id");
    header("Location: clientes.php");
}
?>
</body>
</html>
