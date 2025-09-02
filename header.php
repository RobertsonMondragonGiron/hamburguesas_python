<?php

?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Hamburguesas FERXXO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">🍔 Hamburguesas Ferxxo</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto">
        <a href="clientes.php" class="btn btn-outline-light btn-sm me-1">👤 Clientes</a>
        <a href="empleados.php" class="btn btn-outline-light btn-sm me-1">👥 Empleados</a>
        <a href="tipo_hamburguesa.php" class="btn btn-outline-light btn-sm me-1">🍔 Hamburguesas</a>
        <a href="productos.php" class="btn btn-outline-light btn-sm me-1">📦 Ingredientes</a>
        <a href="ventas.php" class="btn btn-outline-light btn-sm me-1">📋 Ventas</a>
        <a href="reporte_ventas.php" class="btn btn-outline-info btn-sm me-1">📊 Reportes</a>
        <a href="nueva_venta.php" class="btn btn-warning btn-sm">➕ Nueva Venta</a>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-4">