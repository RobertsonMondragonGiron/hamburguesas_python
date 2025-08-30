<?php include("config/db.php"); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nueva venta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .cart-table td, .cart-table th { vertical-align: middle; }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <?php include("header.php"); ?>

 <h2>🛒 Nueva venta</h2>

<?php include("footer.php"); ?>
  

  <?php
  // procesar POST con carrito (campo cart_json)
  if(isset($_POST['cart_json']) && !empty($_POST['cart_json'])){
      $cliente_id = (int)$_POST['cliente_id'];
      $empleado_id = (int)$_POST['empleado_id'];
      $cart = json_decode($_POST['cart_json'], true);
      if(!$cart || count($cart)==0){
          echo "<div class='alert alert-danger'>Carrito vacío.</div>";
      } else {
          // calcular total
          $total = 0;
          foreach($cart as $it) $total += floatval($it['subtotal']);

          // insertar venta
          $stmt = $conn->prepare("INSERT INTO ventas (cliente_id, empleado_id, nombre_cliente, nombre_empleado, hamburguesa, valor) VALUES (?, ?, '', '', ?, ?)");
          // hamburguesa field contenido simple; lo dejamos 'varios' o concatenamos nombres
          $hamb_str = "varios";
          $stmt->bind_param("iisd", $cliente_id, $empleado_id, $hamb_str, $total);
          $stmt->execute();
          $id_venta = $conn->insert_id;

          // insertar detalle
          $ins = $conn->prepare("INSERT INTO detalle_ventas (venta_id, hamburguesa_id, cantidad, subtotal) VALUES (?, ?, ?, ?)");
          foreach($cart as $it){
              $hid = (int)$it['id'];
              $cant = (int)$it['cantidad'];
              $sub = floatval($it['subtotal']);
              $ins->bind_param("iiid", $id_venta, $hid, $cant, $sub);
              $ins->execute();
          }

          echo "<div class='alert alert-success'>✅ Venta registrada. ID venta: $id_venta</div>";
      }
  }
  ?>

  <form id="ventaForm" method="POST">
    <div class="row g-3">
      <div class="col-md-4">
        <label>Cliente</label>
        <select name="cliente_id" id="cliente_id" class="form-select" required>
          <?php
            $rs = $conn->query("SELECT id_cliente, nombre FROM clientes");
            while($r = $rs->fetch_assoc()){
              echo "<option value='{$r['id_cliente']}'>{$r['nombre']}</option>";
            }
          ?>
        </select>
      </div>
      <div class="col-md-4">
        <label>Empleado</label>
        <select name="empleado_id" id="empleado_id" class="form-select" required>
          <?php
            $rs = $conn->query("SELECT id_empleado, nombre FROM empleados");
            while($r = $rs->fetch_assoc()){
              echo "<option value='{$r['id_empleado']}'>{$r['nombre']}</option>";
            }
          ?>
        </select>
      </div>
    </div>

    <hr>

    <div class="row g-3 align-items-end">
      <div class="col-md-5">
        <label>Hamburguesa</label>
        <select id="selectHamb" class="form-select">
          <option value="">-- Selecciona --</option>
          <?php
            $rs = $conn->query("SELECT id, nombre, precio FROM tipo_hamburguesa ORDER BY nombre");
            $hambs = [];
            while($h = $rs->fetch_assoc()){
              // Imprimimos opciones y guardamos en JS-friendly data
              echo "<option value='{$h['id']}' data-precio='{$h['precio']}'>{$h['nombre']} - $".number_format($h['precio'],2)."</option>";
            }
          ?>
        </select>
      </div>

      <div class="col-md-3">
        <label>Cantidad</label>
        <input id="cantidad" type="number" class="form-control" value="1" min="1">
      </div>

      <div class="col-md-2">
        <button id="agregarBtn" type="button" class="btn btn-primary w-100">Agregar</button>
      </div>
    </div>

    <hr>

    <div class="table-responsive">
      <table class="table cart-table bg-white">
        <thead class="table-secondary">
          <tr><th>Hamburguesa</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody id="cartBody"></tbody>
        <tfoot>
          <tr><th colspan="3" class="text-end">Total</th><th id="cartTotal">$0.00</th><th></th></tr>
        </tfoot>
      </table>
    </div>

    <input type="hidden" name="cart_json" id="cart_json">
    <div class="mt-3">
      <button type="submit" class="btn btn-success">Finalizar venta</button>
      <a href="ventas.php" class="btn btn-secondary">Ver ventas</a>
    </div>
  </form>
</div>

<script>
  const selectHamb = document.getElementById('selectHamb');
  const cantidadInput = document.getElementById('cantidad');
  const agregarBtn = document.getElementById('agregarBtn');
  const cartBody = document.getElementById('cartBody');
  const cartTotal = document.getElementById('cartTotal');
  const cartInput = document.getElementById('cart_json');

  let cart = [];

  function formatMoney(v){ return '$' + v.toFixed(2); }

  function renderCart(){
    cartBody.innerHTML = '';
    let total = 0;
    cart.forEach((it, idx) => {
      total += it.subtotal;
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${it.nombre}</td>
                      <td>${it.cantidad}</td>
                      <td>${formatMoney(it.precio)}</td>
                      <td>${formatMoney(it.subtotal)}</td>
                      <td><button class="btn btn-sm btn-danger" onclick="removeItem(${idx})">✖</button></td>`;
      cartBody.appendChild(tr);
    });
    cartTotal.textContent = formatMoney(total);
    cartInput.value = JSON.stringify(cart);
  }

  function removeItem(i){
    cart.splice(i,1);
    renderCart();
  }

  agregarBtn.addEventListener('click', () => {
    const opt = selectHamb.options[selectHamb.selectedIndex];
    const id = parseInt(selectHamb.value);
    if(!id) return alert('Selecciona una hamburguesa');
    const nombre = opt.text;
    const precio = parseFloat(opt.dataset.precio);
    const cantidad = parseInt(cantidadInput.value) || 1;
    const subtotal = parseFloat((precio * cantidad).toFixed(2));

    // si ya existe el item, sumamos cantidades
    const existIdx = cart.findIndex(x => x.id === id);
    if(existIdx >= 0){
      cart[existIdx].cantidad += cantidad;
      cart[existIdx].subtotal = parseFloat((cart[existIdx].cantidad * cart[existIdx].precio).toFixed(2));
    } else {
      cart.push({ id, nombre, precio, cantidad, subtotal });
    }
    renderCart();
  });

  // al enviar el formulario, si carrito vacío, evitar
  document.getElementById('ventaForm').addEventListener('submit', function(e){
    if(cart.length === 0){ e.preventDefault(); alert('El carrito está vacío. Agrega al menos un item.'); return false; }
    cartInput.value = JSON.stringify(cart);
  });

  renderCart();
</script>
</body>
</html>
