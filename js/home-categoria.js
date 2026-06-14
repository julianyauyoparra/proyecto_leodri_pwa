/**
 * LEODRI — Home: scroll a sección o producto desde el menú / enlace directo
 */
(function () {
    'use strict';

    var hash = window.location.hash || '';
    var productoId = hash.indexOf('#producto-') === 0 ? hash.slice(1) : '';

    if (productoId) {
        window.requestAnimationFrame(function () {
            var producto = document.getElementById(productoId);
            if (producto) {
                producto.scrollIntoView({ behavior: 'smooth', block: 'center' });
                producto.classList.add('ficha--scroll-destino');
                window.setTimeout(function () {
                    producto.classList.remove('ficha--scroll-destino');
                }, 2400);
                return;
            }

            var seccion = document.getElementById('tienda-productos');
            if (seccion) {
                seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        return;
    }

    var seccion = document.getElementById('tienda-productos');
    if (!seccion) {
        return;
    }

    if (hash === '#tienda-productos' || window.location.search.indexOf('categoria=') >= 0) {
        window.requestAnimationFrame(function () {
            seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
})();
