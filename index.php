<?php include("header.php"); ?>

<div class="text-center">
  <h1 class="display-5">Bienvenido al sistema 🍔</h1>
  <p class="lead">Gestiona clientes, empleados, inventario y ventas.</p>
  
  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">👤 Clientes</h5>
          <p>Gestiona la información de tus clientes.</p>
          <a href="clientes.php" class="btn btn-primary">Ver clientes</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🧑‍🍳 Empleados</h5>
          <p>Gestiona el personal que atiende el restaurante.</p>
          <a href="empleados.php" class="btn btn-primary">Ver empleados</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">📦 Inventario</h5>
          <p>Control de ingredientes disponibles.</p>
          <a href="productos.php" class="btn btn-primary">Ver Ingredientes</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include("footer.php"); ?>

