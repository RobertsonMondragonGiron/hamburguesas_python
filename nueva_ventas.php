<?php
/
?>

<script>

function validarStock(hamburguesaId, cantidad) {
    
    return true;
}


agregarBtn.addEventListener('click', () => {
    const selectedOption = selectHamb.options[selectHamb.selectedIndex];
    const id = parseInt(selectHamb.value);
    
    if(!id) {
        alert('Selecciona una hamburguesa');
        return;
    }
    
    const cantidad = parseInt(cantidadInput.value) || 1;
    if(cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0');
        return;
    }
    
    const nombre = selectedOption.text.split(' - $')[0];
    const precio = parseFloat(selectedOption.dataset.precio);
    const subtotal = parseFloat((precio * cantidad).toFixed(2));

   
    const existingIndex = cart.findIndex(item => item.id === id);
    if(existingIndex >= 0){
        cart[existingIndex].cantidad += cantidad;
        cart[existingIndex].subtotal = parseFloat((cart[existingIndex].cantidad * cart[existingIndex].precio).toFixed(2));
    } else {
        cart.push({ id, nombre, precio, cantidad, subtotal });
    }
    
    renderCart();
    selectHamb.value = '';
    cantidadInput.value = 1;
});
</script>