/**
 * LEODRI.pe V2 — Configuración del navegador (producción / base en repo).
 * whatsapp: código de país + número, sin +, espacios ni guiones.
 *
 * Local: crea js/leodri-config.local.js (ver leodri-config.local.example.js).
 * cPanel: este archivo debe tener la URL Railway del agente (sin localhost).
 */
window.LEODRI_CONFIG = Object.freeze({
    whatsapp: '51935486809',
    stockApiUrl: 'https://agente-leodrian-production.up.railway.app/api/stock'
});
