(function () {
    'use strict';

    var precioInput = document.getElementById('precio');
    var precioAnteriorInput = document.getElementById('precio_anterior');
    if (precioInput && precioAnteriorInput) {
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
    }

    var stockLista = document.getElementById('stock-lista');
    var tplStock = document.getElementById('tpl-stock-row');
    if (stockLista && tplStock) {
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-add-stock]')) {
                stockLista.appendChild(tplStock.content.cloneNode(true));
                return;
            }

            var quitar = e.target.closest('[data-remove-stock]');
            if (quitar) {
                var filas = stockLista.querySelectorAll('[data-stock-row]');
                if (filas.length > 1) {
                    quitar.closest('[data-stock-row]').remove();
                }
            }
        });
    }
})();
