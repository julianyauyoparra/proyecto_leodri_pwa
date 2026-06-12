/**
 * LEODRI — Home: scroll a sección de categoría desde el menú
 */
(function () {
    'use strict';

    var seccion = document.getElementById('tienda-productos');
    if (!seccion) {
        return;
    }

    if (window.location.hash === '#tienda-productos' || window.location.search.indexOf('categoria=') >= 0) {
        window.requestAnimationFrame(function () {
            seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
})();
