<?php

?>

<?php

include("config/db.php");

if(isset($_POST['hacer_backup'])){
    $fecha = date('Y-m-d_H-i-s');
    $filename = "backup_hamburguesas_$fecha.sql";
    
    
    $command = "mysqldump -u root hamburguesas_db > backups/$filename";
    
    if(!file_exists('backups')){
        mkdir('backups', 0777, true);
    }
    
    system($command, $result);
    
    if($result == 0){
        echo "<div class='alert alert-success'>✅ Backup creado: $filename</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error al crear backup</div>";
    }
}

include("header.php");
?>

<h2>💾 Backup Base de Datos</h2>

<div class="card">
    <div class="card-body">
        <p>Crea una copia de seguridad de la base de datos actual.</p>
        <form method="POST">
            <button name="hacer_backup" class="btn btn-primary" onclick="return confirm('¿Crear backup de la base de datos?')">
                💾 Crear Backup
            </button>
        </form>
    </div>
</div>

<div class="mt-4">
    <h5>Backups Existentes</h5>
    <ul class="list-group">
        <?php
        if(file_exists('backups')){
            $backups = glob('backups/*.sql');
            foreach($backups as $backup){
                $filename = basename($backup);
                $size = filesize($backup);
                $date = date('d/m/Y H:i:s', filemtime($backup));
                echo "<li class='list-group-item d-flex justify-content-between'>
                        <span>$filename <small class='text-muted'>($date - ".number_format($size/1024, 2)." KB)</small></span>
                        <a href='$backup' download class='btn btn-sm btn-outline-primary'>📥 Descargar</a>
                      </li>";
            }
        } else {
            echo "<li class='list-group-item'>No hay backups disponibles</li>";
        }
        ?>
    </ul>
</div>

<?php include("footer.php"); ?>