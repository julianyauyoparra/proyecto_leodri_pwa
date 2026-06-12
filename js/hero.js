/**
 * LEODRI — Carrusel hero (rotación automática)
 */
(function () {
    'use strict';

    var hero = document.querySelector('.tienda-hero');
    if (!hero) return;

    var slides = hero.querySelectorAll('[data-hero-slide]');
    var dots = hero.querySelectorAll('[data-hero-dot]');
    var btnPrev = hero.querySelector('[data-hero-prev]');
    var btnNext = hero.querySelector('[data-hero-next]');

    if (slides.length <= 1) return;

    var intervalo = parseInt(hero.getAttribute('data-hero-interval') || '3000', 10);
    if (!intervalo || intervalo < 1000) intervalo = 3000;

    var indice = 0;
    var timer = null;

    function mostrar(nuevoIndice) {
        indice = (nuevoIndice + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            var activo = i === indice;
            slide.classList.toggle('is-active', activo);
            slide.setAttribute('aria-hidden', activo ? 'false' : 'true');
        });

        dots.forEach(function (dot, i) {
            var activo = i === indice;
            dot.classList.toggle('is-active', activo);
            dot.setAttribute('aria-selected', activo ? 'true' : 'false');
        });
    }

    function siguiente() {
        mostrar(indice + 1);
    }

    function anterior() {
        mostrar(indice - 1);
    }

    function iniciar() {
        detener();
        timer = window.setInterval(siguiente, intervalo);
    }

    function detener() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function reiniciar() {
        iniciar();
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function () {
            anterior();
            reiniciar();
        });
    }

    if (btnNext) {
        btnNext.addEventListener('click', function () {
            siguiente();
            reiniciar();
        });
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var destino = parseInt(dot.getAttribute('data-hero-dot') || '0', 10);
            mostrar(destino);
            reiniciar();
        });
    });

    hero.addEventListener('mouseenter', detener);
    hero.addEventListener('mouseleave', iniciar);
    hero.addEventListener('focusin', detener);
    hero.addEventListener('focusout', function (event) {
        if (!hero.contains(event.relatedTarget)) {
            iniciar();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            detener();
        } else {
            iniciar();
        }
    });

    iniciar();
})();
