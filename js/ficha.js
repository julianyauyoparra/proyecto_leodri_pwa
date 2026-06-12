/**
 * LEODRI.pe — Catálogo: grilla, modales, tallas, colores, CTA WhatsApp
 */
(function () {
    'use strict';

    const MSG_AYUDA = 'Selecciona color y talla';
    const MSG_AYUDA_TALLA = 'Selecciona talla';
    const MSG_EXITO = '¡Excelente Elección!';
    const VISTAS_CARRUSEL = ['derecha', 'frente', 'izquierda', 'posterior', 'arriba', 'abajo'];
    const CARRUSEL_INTERVALO_MS = 2800;
    const SOPORTA_HOVER = window.matchMedia('(hover: hover) and (pointer: fine)');

    let stickyBarsCtrl = null;

    function obtenerNumeroWhatsApp() {
        const config = window.LEODRI_CONFIG || {};
        return String(config.whatsapp || '').replace(/\D/g, '');
    }

    function resolverUrlAbsoluta(ruta) {
        if (!ruta) return '';
        if (/^https?:\/\//i.test(ruta)) return ruta;
        try {
            return new URL(ruta, window.location.href).href;
        } catch (e) {
            return ruta;
        }
    }

    function obtenerImagenProducto(ficha) {
        const thumb = ficha.querySelector('.ficha-thumb.is-active');
        let ruta = '';

        if (thumb && thumb.dataset.imagen) {
            ruta = thumb.dataset.imagen;
        } else {
            const heroImg = ficha.querySelector('.ficha-galeria__hero-img');
            ruta = heroImg ? (heroImg.getAttribute('src') || '') : '';
        }

        return resolverUrlAbsoluta(ruta);
    }

    function construirMensajeWhatsApp(ficha, urlImagen) {
        const skuEl = ficha.querySelector('[data-tag="sku"]');
        const sku = skuEl ? skuEl.textContent.trim() : '';

        const lineas = [
            '¡Hola LEODRIAN! Quiero este par:',
            '',
            'SKU: ' + sku
        ];

        if (urlImagen) {
            lineas.push('', 'Imagen:', urlImagen);
        }

        return lineas.join('\n');
    }

    function abrirWhatsApp(mensaje) {
        const numero = obtenerNumeroWhatsApp();
        if (!numero) {
            console.warn('LEODRI: configura LEODRI_CONFIG.whatsapp en js/leodri-config.js');
            return false;
        }

        const url = 'https://wa.me/' + numero + '?text=' + encodeURIComponent(mensaje);
        window.open(url, '_blank', 'noopener,noreferrer');
        return true;
    }

    function obtenerColorActivoCodigo(ficha) {
        const thumb = ficha.querySelector('.ficha-thumb.is-active');
        if (thumb && thumb.dataset.color) {
            return thumb.dataset.color;
        }
        return '';
    }

    function marcarStockTemporalLocal(ficha, colorCodigo, talla, aplicarDisponibilidadTallas) {
        ficha.querySelectorAll('.ficha-thumb').forEach(function (thumb) {
            if ((thumb.dataset.color || '') !== colorCodigo) {
                return;
            }
            let mapa = {};
            try {
                mapa = JSON.parse(thumb.dataset.tallasDisponibles || '{}');
            } catch (e) {
                mapa = {};
            }
            mapa[talla] = false;
            thumb.dataset.tallasDisponibles = JSON.stringify(mapa);
        });

        const thumbActivo = ficha.querySelector('.ficha-thumb.is-active');
        if (thumbActivo && aplicarDisponibilidadTallas) {
            let mapaActivo = {};
            try {
                mapaActivo = JSON.parse(thumbActivo.dataset.tallasDisponibles || '{}');
            } catch (e) {
                mapaActivo = {};
            }
            aplicarDisponibilidadTallas(mapaActivo);
        }
    }

    function initLightbox() {
        const lightbox = document.getElementById('ficha-lightbox');
        if (!lightbox) return null;

        const img = lightbox.querySelector('.ficha-lightbox__img');
        const btnCerrar = lightbox.querySelector('.ficha-lightbox__cerrar');
        const btnPrev = lightbox.querySelector('.ficha-lightbox__nav--prev');
        const btnNext = lightbox.querySelector('.ficha-lightbox__nav--next');
        const contador = lightbox.querySelector('.ficha-lightbox__contador');
        const figure = lightbox.querySelector('.ficha-lightbox__figure');
        const zoomWrap = lightbox.querySelector('.ficha-lightbox__zoom-wrap');
        const backdrop = lightbox.querySelector('[data-lightbox-cerrar]');
        let ultimoFoco = null;
        let urls = [];
        let indice = 0;
        let altBase = '';

        function estaAbierto() {
            return lightbox.classList.contains('is-abierto');
        }

        function resetZoom() {
            if (figure) {
                figure.classList.remove('is-zooming');
            }
            if (img) {
                img.style.transformOrigin = 'center center';
            }
        }

        function actualizarControles() {
            const multiples = urls.length > 1;
            if (btnPrev) btnPrev.hidden = !multiples;
            if (btnNext) btnNext.hidden = !multiples;
            if (contador) {
                contador.hidden = !multiples;
                contador.textContent = (indice + 1) + ' / ' + urls.length;
            }
        }

        function mostrarIndice(nuevo) {
            if (!img || urls.length === 0) return;
            resetZoom();
            indice = ((nuevo % urls.length) + urls.length) % urls.length;
            img.src = urls[indice];
            img.alt = altBase;
            actualizarControles();
        }

        function anterior() {
            mostrarIndice(indice - 1);
        }

        function siguiente() {
            mostrarIndice(indice + 1);
        }

        function cerrar() {
            lightbox.classList.remove('is-abierto');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ficha-lightbox-abierto');
            if (stickyBarsCtrl) stickyBarsCtrl.actualizar();
            urls = [];
            indice = 0;
            altBase = '';
            resetZoom();
            if (img) {
                img.removeAttribute('src');
                img.alt = '';
            }
            actualizarControles();
            if (ultimoFoco && typeof ultimoFoco.focus === 'function') {
                ultimoFoco.focus();
            }
            ultimoFoco = null;
        }

        function abrir(srcOrUrls, alt, indiceInicial) {
            if (!img) return;

            if (Array.isArray(srcOrUrls)) {
                urls = srcOrUrls.filter(Boolean);
            } else if (srcOrUrls) {
                urls = [srcOrUrls];
            } else {
                return;
            }

            if (urls.length === 0) return;

            altBase = alt || '';
            ultimoFoco = document.activeElement;
            mostrarIndice(typeof indiceInicial === 'number' ? indiceInicial : 0);
            lightbox.classList.add('is-abierto');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ficha-lightbox-abierto');
            if (stickyBarsCtrl) stickyBarsCtrl.actualizar();
            if (btnCerrar) btnCerrar.focus();
        }

        if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
        if (backdrop) backdrop.addEventListener('click', cerrar);
        if (btnPrev) {
            btnPrev.addEventListener('click', function (e) {
                e.stopPropagation();
                anterior();
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function (e) {
                e.stopPropagation();
                siguiente();
            });
        }

        if (figure && zoomWrap && img && SOPORTA_HOVER.matches) {
            figure.addEventListener('mouseenter', function () {
                figure.classList.add('is-zooming');
            });
            figure.addEventListener('mouseleave', resetZoom);
            figure.addEventListener('mousemove', function (e) {
                if (!figure.classList.contains('is-zooming')) {
                    return;
                }
                const rect = zoomWrap.getBoundingClientRect();
                if (rect.width <= 0 || rect.height <= 0) {
                    return;
                }
                const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
                img.style.transformOrigin = x + '% ' + y + '%';
            });
        }

        return { abrir, cerrar, estaAbierto, anterior, siguiente };
    }

    function initDetallesModal() {
        const panel = document.getElementById('ficha-detalles');
        const guiasRoot = document.getElementById('ficha-guias-serie');
        if (!panel || !guiasRoot) return null;

        const contenido = panel.querySelector('[data-detalles-contenido]');
        const guiaDestino = panel.querySelector('[data-detalles-guia]');
        const guiaTitulo = panel.querySelector('[data-detalles-guia-titulo]');
        const btnCerrar = panel.querySelector('.ficha-detalles__cerrar');
        const backdrop = panel.querySelector('[data-detalles-cerrar]');
        let ultimoFoco = null;

        function estaAbierto() {
            return panel.classList.contains('is-abierto');
        }

        function cerrar() {
            panel.classList.remove('is-abierto');
            panel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ficha-detalles-abierta');
            if (stickyBarsCtrl) stickyBarsCtrl.actualizar();
            if (contenido) contenido.innerHTML = '';
            if (guiaDestino) guiaDestino.innerHTML = '';
            if (ultimoFoco && typeof ultimoFoco.focus === 'function') {
                ultimoFoco.focus();
            }
            ultimoFoco = null;
        }

        function abrir(ficha) {
            if (!contenido || !guiaDestino) return;

            const tpl = ficha ? ficha.querySelector('.ficha-detalles-tpl') : null;
            contenido.innerHTML = '';
            if (tpl && tpl.content) {
                contenido.appendChild(tpl.content.cloneNode(true));
            }

            const serie = ficha ? (ficha.dataset.serie || 'escolar') : 'escolar';
            const guiaTpl = guiasRoot.querySelector('.ficha-guia-serie[data-serie="' + serie + '"]');
            guiaDestino.innerHTML = '';
            if (guiaTpl) {
                if (guiaTitulo) {
                    guiaTitulo.textContent = guiaTpl.getAttribute('data-titulo') || 'Guía de tallas';
                }
                guiaDestino.appendChild(guiaTpl.cloneNode(true));
            }

            ultimoFoco = document.activeElement;
            panel.classList.add('is-abierto');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ficha-detalles-abierta');
            if (stickyBarsCtrl) stickyBarsCtrl.actualizar();
            if (btnCerrar) btnCerrar.focus();
        }

        if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
        if (backdrop) backdrop.addEventListener('click', cerrar);

        return { abrir, cerrar, estaAbierto };
    }

    function modalAbierto() {
        return document.body.classList.contains('ficha-detalles-abierta')
            || document.body.classList.contains('ficha-lightbox-abierto');
    }

    function stickySoloMovil() {
        return window.matchMedia('(max-width: 1023px)').matches;
    }

    function ocultarStickyBars(items) {
        items.forEach(function (item) {
            item.sticky.classList.remove('is-visible');
            item.sticky.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('pagina-ficha--con-sticky');
    }

    function initStickyBars() {
        const items = [];

        document.querySelectorAll('.ficha').forEach(function (ficha) {
            const footer = ficha.querySelector('.ficha-accion');
            const sticky = ficha.querySelector('[data-ficha-sticky]');
            if (footer && sticky) {
                items.push({ ficha: ficha, footer: footer, sticky: sticky });
            }
        });

        function actualizarVisibilidad() {
            if (!stickySoloMovil() || modalAbierto()) {
                ocultarStickyBars(items);
                return;
            }

            let activo = null;
            let mayorVisibilidad = 0;

            items.forEach(function (item) {
                item.sticky.classList.remove('is-visible');
                item.sticky.setAttribute('aria-hidden', 'true');

                const footerRect = item.footer.getBoundingClientRect();
                const fichaRect = item.ficha.getBoundingClientRect();
                const footerVisible = footerRect.top < window.innerHeight && footerRect.bottom > 0;
                const fichaVisible = fichaRect.bottom > 0 && fichaRect.top < window.innerHeight;

                if (!footerVisible && fichaVisible) {
                    const visible = Math.min(fichaRect.bottom, window.innerHeight) - Math.max(fichaRect.top, 0);
                    if (visible > mayorVisibilidad) {
                        mayorVisibilidad = visible;
                        activo = item;
                    }
                }
            });

            if (activo) {
                activo.sticky.classList.add('is-visible');
                activo.sticky.setAttribute('aria-hidden', 'false');
                document.body.classList.add('pagina-ficha--con-sticky');
            } else {
                document.body.classList.remove('pagina-ficha--con-sticky');
            }
        }

        window.addEventListener('scroll', actualizarVisibilidad, { passive: true });
        window.addEventListener('resize', actualizarVisibilidad);
        actualizarVisibilidad();

        return { actualizar: actualizarVisibilidad };
    }

    function initFicha(ficha, lightbox, detallesModal) {
        const hero = ficha.querySelector('.ficha-galeria__hero');
        const heroImg = ficha.querySelector('.ficha-galeria__hero-img');
        const heroMenu = ficha.querySelector('.ficha-hero-menu');
        const btnZoom = ficha.querySelector('[data-hero-zoom]');
        const btnDetalles = ficha.querySelector('[data-hero-detalles]');
        const thumbs = ficha.querySelectorAll('.ficha-thumb');
        const totalColores = Array.prototype.filter.call(thumbs, function (thumb) {
            return Boolean(thumb.dataset.imagen);
        }).length;
        const unSoloColor = totalColores <= 1;

        function mensajeAyudaPendiente() {
            if (unSoloColor || colorSeleccionado) {
                return MSG_AYUDA_TALLA;
            }
            return MSG_AYUDA;
        }

        let carruselUrls = [];
        let carruselIndice = 0;
        let carruselTimer = null;
        let carruselPausado = false;
        const tallas = ficha.querySelectorAll('.ficha-talla');
        const ctaWrap = ficha.querySelector('.ficha-accion__cta-wrap');
        const cta = ficha.querySelector('.ficha-accion__cta');
        const ayuda = ficha.querySelector('[data-ayuda-cta]');
        const tagSku = ficha.querySelector('[data-tag="sku"]');
        const sticky = ficha.querySelector('[data-ficha-sticky]');
        const ctaSticky = sticky ? sticky.querySelector('[data-sticky-cta]') : null;
        const precioSticky = sticky ? sticky.querySelector('[data-sticky-precio]') : null;
        const tallaSticky = sticky ? sticky.querySelector('[data-sticky-talla]') : null;
        const tallaWrap = sticky ? sticky.querySelector('[data-sticky-talla-wrap]') : null;
        const precioMain = ficha.querySelector('.ficha-precios__actual');

        let tallaSeleccionada = null;
        let colorSeleccionado = false;
        let menuHeroVisible = false;

        function obtenerUrlsLightbox() {
            const thumb = ficha.querySelector('.ficha-thumb.is-active') || obtenerThumbInicial();
            let imagenes = {};

            if (thumb) {
                try {
                    imagenes = JSON.parse(thumb.dataset.imagenes || '{}');
                } catch (e) {
                    imagenes = {};
                }
            }

            const urls = [];
            VISTAS_CARRUSEL.forEach(function (clave) {
                if (imagenes[clave]) {
                    urls.push(resolverUrlAbsoluta(imagenes[clave]));
                }
            });

            if (urls.length === 0 && heroImg && heroImg.src) {
                urls.push(resolverUrlAbsoluta(heroImg.src));
            }

            return urls;
        }

        function abrirImagenAmpliada() {
            if (!lightbox) return;

            asegurarColorSeleccionado();
            const urls = obtenerUrlsLightbox();
            if (urls.length === 0) return;

            ocultarMenuHero();

            let indiceInicial = 0;
            if (heroImg && heroImg.src) {
                const actual = resolverUrlAbsoluta(heroImg.src);
                const posicion = urls.indexOf(actual);
                if (posicion >= 0) {
                    indiceInicial = posicion;
                }
            }

            lightbox.abrir(urls, heroImg ? heroImg.alt : '', indiceInicial);
        }

        function mostrarMenuHero() {
            if (!heroMenu) return;
            menuHeroVisible = true;
            heroMenu.hidden = false;
            heroMenu.setAttribute('aria-hidden', 'false');
            hero.classList.add('is-menu-abierto');
        }

        function ocultarMenuHero() {
            if (!heroMenu) return;
            menuHeroVisible = false;
            heroMenu.hidden = true;
            heroMenu.setAttribute('aria-hidden', 'true');
            hero.classList.remove('is-menu-abierto');
            carruselPausado = false;
        }

        function detenerCarrusel() {
            if (carruselTimer) {
                clearInterval(carruselTimer);
                carruselTimer = null;
            }
        }

        function mostrarCarruselIndice(indice) {
            if (!heroImg || carruselUrls.length === 0) return;
            carruselIndice = indice;
            heroImg.src = carruselUrls[carruselIndice];
        }

        function avanzarCarrusel() {
            if (carruselPausado || carruselUrls.length <= 1 || !colorSeleccionado) return;
            mostrarCarruselIndice((carruselIndice + 1) % carruselUrls.length);
        }

        function iniciarCarrusel() {
            detenerCarrusel();
            if (!colorSeleccionado || carruselUrls.length <= 1) return;
            carruselTimer = setInterval(avanzarCarrusel, CARRUSEL_INTERVALO_MS);
        }

        function prepararCarrusel(imagenes) {
            carruselUrls = [];
            VISTAS_CARRUSEL.forEach(function (clave) {
                if (imagenes[clave]) {
                    carruselUrls.push(imagenes[clave]);
                }
            });
            carruselIndice = 0;
            if (colorSeleccionado && carruselUrls.length > 0) {
                mostrarCarruselIndice(0);
                iniciarCarrusel();
            }
        }

        function esTallaDisponible(mapa, numero) {
            if (!mapa || typeof mapa !== 'object') {
                return false;
            }
            if (Object.prototype.hasOwnProperty.call(mapa, numero)) {
                return Boolean(mapa[numero]);
            }
            return null;
        }

        function resetTallas() {
            tallaSeleccionada = null;
            tallas.forEach(function (t) {
                t.classList.remove('is-selected');
                t.setAttribute('aria-checked', 'false');
            });
            actualizarCta();
        }

        function aplicarDisponibilidadTallas(mapa) {
            tallas.forEach(function (btn) {
                const numero = btn.dataset.talla || '';
                let disponible = esTallaDisponible(mapa, numero);
                if (disponible === null) {
                    disponible = !btn.classList.contains('is-agotada');
                }
                btn.classList.toggle('is-agotada', !disponible);
                btn.setAttribute('aria-disabled', disponible ? 'false' : 'true');
                btn.tabIndex = disponible ? 0 : -1;
                if (!disponible) {
                    btn.classList.remove('is-selected');
                    btn.setAttribute('aria-checked', 'false');
                }
            });
            if (tallaSeleccionada && mapa[tallaSeleccionada] === false) {
                tallaSeleccionada = null;
                actualizarCta();
            }
        }

        function aplicarColorThumb(btn) {
            if (!btn) return;
            let imagenes = {};
            let tallasDisponibles = {};
            try {
                imagenes = JSON.parse(btn.dataset.imagenes || '{}');
            } catch (e) {
                imagenes = {};
            }
            try {
                tallasDisponibles = JSON.parse(btn.dataset.tallasDisponibles || '{}');
            } catch (e) {
                tallasDisponibles = {};
            }
            prepararCarrusel(imagenes);
            aplicarDisponibilidadTallas(tallasDisponibles);
            if (heroImg && btn.dataset.imagen) {
                heroImg.src = btn.dataset.imagen;
            }
            if (heroImg && btn.dataset.alt) {
                heroImg.alt = btn.dataset.alt;
            }
        }

        function actualizarCta() {
            const listo = colorSeleccionado && tallaSeleccionada !== null;

            if (ctaWrap) {
                ctaWrap.hidden = !listo;
            }
            if (ctaSticky) {
                ctaSticky.hidden = !listo;
            }

            if (precioSticky && precioMain) {
                precioSticky.textContent = precioMain.textContent;
            }

            if (tallaSticky && tallaWrap) {
                if (listo) {
                    tallaSticky.textContent = tallaSeleccionada;
                    tallaWrap.classList.add('is-seleccionada');
                } else {
                    tallaSticky.textContent = '—';
                    tallaWrap.classList.remove('is-seleccionada');
                }
            }

            if (ayuda) {
                ayuda.textContent = listo ? MSG_EXITO : mensajeAyudaPendiente();
                ayuda.classList.toggle('is-exito', listo);
                ayuda.hidden = listo;
            }
        }

        function actualizarSkuColor(thumb) {
            if (!tagSku || !thumb) return;

            if (thumb.dataset.skuBase) {
                tagSku.dataset.skuBase = thumb.dataset.skuBase;
            }
            if (thumb.dataset.skuSinTalla) {
                tagSku.dataset.skuSinTalla = thumb.dataset.skuSinTalla;
            }

            if (tallaSeleccionada) {
                actualizarSku();
            } else if (tagSku.dataset.skuSinTalla) {
                tagSku.textContent = tagSku.dataset.skuSinTalla;
            }
        }

        function actualizarSku() {
            if (!tagSku || !tallaSeleccionada) return;
            const skuBase = tagSku.dataset.skuBase || '';
            if (skuBase) {
                tagSku.textContent = skuBase.replace('{talla}', tallaSeleccionada);
            }
        }

        function activarThumb(btn) {
            if (!btn || !btn.dataset.imagen) {
                return false;
            }

            thumbs.forEach(function (t) {
                t.classList.remove('is-active');
                t.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('is-active');
            btn.setAttribute('aria-selected', 'true');

            colorSeleccionado = true;
            aplicarColorThumb(btn);
            actualizarSkuColor(btn);
            resetTallas();
            actualizarCta();
            return true;
        }

        function obtenerThumbInicial() {
            const codigoDefecto = ficha.dataset.colorDefault || '';
            if (codigoDefecto) {
                const coincidencia = ficha.querySelector('.ficha-thumb[data-color="' + codigoDefecto + '"]');
                if (coincidencia && coincidencia.dataset.imagen) {
                    return coincidencia;
                }
            }

            const thumbsConImagen = Array.prototype.filter.call(thumbs, function (thumb) {
                return Boolean(thumb.dataset.imagen);
            });

            return thumbsConImagen[0] || null;
        }

        function asegurarColorSeleccionado() {
            if (colorSeleccionado) {
                return true;
            }
            return activarThumb(obtenerThumbInicial());
        }

        if (btnZoom) {
            btnZoom.addEventListener('click', function (e) {
                e.stopPropagation();
                abrirImagenAmpliada();
            });
        }

        if (btnDetalles) {
            btnDetalles.addEventListener('click', function (e) {
                e.stopPropagation();
                ocultarMenuHero();
                if (detallesModal) {
                    detallesModal.abrir(ficha);
                }
            });
        }

        if (hero && heroMenu) {
            function dispositivoConHover() {
                return SOPORTA_HOVER.matches;
            }

            if (dispositivoConHover()) {
                hero.addEventListener('mouseenter', function () {
                    carruselPausado = true;
                    mostrarMenuHero();
                });
                hero.addEventListener('mouseleave', function () {
                    carruselPausado = false;
                    ocultarMenuHero();
                });
            } else {
                hero.addEventListener('click', function (e) {
                    if (e.target.closest('.ficha-hero-menu__opcion')) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    if (menuHeroVisible) {
                        ocultarMenuHero();
                    } else {
                        carruselPausado = true;
                        mostrarMenuHero();
                    }
                });

                document.addEventListener('click', function (e) {
                    if (dispositivoConHover()) {
                        return;
                    }
                    if (!menuHeroVisible) {
                        return;
                    }
                    if (hero.contains(e.target)) {
                        return;
                    }
                    carruselPausado = false;
                    ocultarMenuHero();
                });
            }
        }

        if (ctaSticky && cta) {
            ctaSticky.addEventListener('click', function () {
                cta.click();
            });
        }

        if (cta) {
            cta.addEventListener('click', function () {
                if (!colorSeleccionado || tallaSeleccionada === null) {
                    return;
                }

                const talla = tallaSeleccionada;
                const color = obtenerColorActivoCodigo(ficha);
                const urlImagen = obtenerImagenProducto(ficha);
                const mensaje = construirMensajeWhatsApp(ficha, urlImagen);

                marcarStockTemporalLocal(ficha, color, talla, aplicarDisponibilidadTallas);
                colorSeleccionado = false;
                thumbs.forEach(function (t) {
                    t.classList.remove('is-active');
                    t.setAttribute('aria-selected', 'false');
                });
                detenerCarrusel();
                resetTallas();
                abrirWhatsApp(mensaje);
            });
        }

        thumbs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activarThumb(btn);
            });
        });

        tallas.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!asegurarColorSeleccionado()) {
                    return;
                }
                if (btn.classList.contains('is-agotada')) {
                    return;
                }

                tallas.forEach(function (t) {
                    t.classList.remove('is-selected');
                    t.setAttribute('aria-checked', 'false');
                });
                btn.classList.add('is-selected');
                btn.setAttribute('aria-checked', 'true');

                tallaSeleccionada = btn.dataset.talla || null;
                actualizarCta();
                actualizarSku();
            });
        });

        function construirUrlStock(productoId) {
            const config = window.LEODRI_CONFIG || {};
            const base = String(config.stockApiUrl || 'api/stock.php').trim();

            if (/^https?:\/\//i.test(base)) {
                const separador = base.indexOf('?') >= 0 ? '&' : '?';
                return base + separador + 'producto_id=' + encodeURIComponent(productoId);
            }

            return base + '?producto_id=' + encodeURIComponent(productoId);
        }

        function refrescarStockDesdeApi() {
            const productoId = ficha.dataset.productoId || '';
            if (!productoId) {
                return;
            }

            fetch(construirUrlStock(productoId), { cache: 'no-store' })
                .then(function (response) {
                    if (!response.ok) throw new Error('stock');
                    return response.json();
                })
                .then(function (data) {
                    if (!data || !data.ok || !data.colores) return;

                    ficha.querySelectorAll('.ficha-thumb').forEach(function (thumb) {
                        const codigo = thumb.dataset.color || '';
                        const mapaColor = data.colores[codigo];
                        if (!mapaColor) return;
                        thumb.dataset.tallasDisponibles = JSON.stringify(mapaColor);
                    });

                    const activo = ficha.querySelector('.ficha-thumb.is-active');
                    if (activo) {
                        aplicarColorThumb(activo);
                    }
                })
                .catch(function () {});
        }

        refrescarStockDesdeApi();
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                refrescarStockDesdeApi();
            }
        });

        activarThumb(obtenerThumbInicial());
        actualizarCta();
    }

    stickyBarsCtrl = initStickyBars();
    const lightbox = initLightbox();
    const detallesModal = initDetallesModal();

    document.addEventListener('keydown', function (e) {
        if (lightbox && lightbox.estaAbierto()) {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                lightbox.anterior();
                return;
            }
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                lightbox.siguiente();
                return;
            }
            if (e.key === 'Escape') {
                lightbox.cerrar();
            }
            return;
        }

        if (e.key !== 'Escape') return;
        if (detallesModal && detallesModal.estaAbierto()) {
            detallesModal.cerrar();
        }
    });

    document.querySelectorAll('.ficha').forEach(function (ficha) {
        initFicha(ficha, lightbox, detallesModal);
    });
})();
