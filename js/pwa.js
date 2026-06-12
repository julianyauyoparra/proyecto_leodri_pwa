/**
 * LEODRI.pe V2 — Registro silencioso del Service Worker
 */
(function () {
    'use strict';

    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('./sw.js').catch(function () {
            /* Sin SW: la web sigue funcionando con red */
        });
    });
})();
