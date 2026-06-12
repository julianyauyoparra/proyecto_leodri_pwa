(function () {
    'use strict';

    var precioInput = document.getElementById('precio');
    var precioAnteriorInput = document.getElementById('precio_anterior');
    if (!precioInput || !precioAnteriorInput) {
        return;
    }

    function sugerirPrecioAnterior() {
        if (precioAnteriorInput.dataset.autoPrecio !== '1') {
            return;
        }
        var precio = parseFloat(precioInput.value);
        if (!precio || precio <= 0) {
            return;
        }
        precioAnteriorInput.value = (Math.round(precio * 1.27 * 100) / 100).toFixed(2);
    }

    precioInput.addEventListener('input', sugerirPrecioAnterior);

    precioAnteriorInput.addEventListener('input', function () {
        precioAnteriorInput.dataset.autoPrecio = '0';
    });

    if (precioAnteriorInput.value === '' || parseFloat(precioAnteriorInput.value) <= 0) {
        sugerirPrecioAnterior();
    }
})();
