(function () {
    'use strict';

    var VISTAS = ['frente', 'derecha', 'izquierda', 'posterior', 'arriba', 'abajo'];
    var previewUrls = new WeakMap();

    var listas = {
        color: 'colores-lista'
    };

    function indiceColorSiguiente() {
        return document.querySelectorAll('#colores-lista .admin-repeater--color').length;
    }

    function agregarRepeater(tipo) {
        var tpl = document.getElementById('tpl-' + tipo);
        var listaId = listas[tipo];
        var lista = listaId ? document.getElementById(listaId) : null;
        if (!tpl || !lista) return;

        if (tipo === 'color') {
            var html = tpl.innerHTML.replace(/__INDEX__/g, String(indiceColorSiguiente()));
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            while (wrap.firstChild) {
                lista.appendChild(wrap.firstChild);
            }
            return;
        }

        lista.appendChild(tpl.content.cloneNode(true));
    }

    function vistaDesdeNombre(nombre) {
        var base = String(nombre).replace(/\.[^.]+$/, '').toLowerCase();
        for (var i = 0; i < VISTAS.length; i++) {
            if (base.indexOf(VISTAS[i]) !== -1) {
                return VISTAS[i];
            }
        }
        return null;
    }

    function limpiarPreviewUrls(input) {
        var urls = previewUrls.get(input);
        if (!urls) return;
        urls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        previewUrls.delete(input);
    }

    function obtenerSlotPreview(colorRepeater, vista) {
        var grid = colorRepeater.querySelector('[data-upload-previews]');
        return grid ? grid.querySelector('[data-vista="' + vista + '"]') : null;
    }

    function procesarLoteImagenes(input) {
        limpiarPreviewUrls(input);

        var colorRepeater = input.closest('.admin-repeater--color');
        var avisos = input.parentElement.querySelector('.admin-upload-lote__avisos');
        if (!colorRepeater || !avisos) return;

        avisos.innerHTML = '';
        avisos.hidden = true;

        var files = input.files ? Array.prototype.slice.call(input.files) : [];
        if (files.length === 0) {
            return;
        }

        var urls = [];
        var vistasUsadas = {};
        var sinVista = [];

        files.forEach(function (file) {
            var vista = vistaDesdeNombre(file.name);
            if (!vista) {
                sinVista.push(file.name);
                return;
            }
            if (vistasUsadas[vista]) {
                sinVista.push(file.name + ' (vista «' + vista + '» duplicada)');
                return;
            }
            vistasUsadas[vista] = file.name;

            var slot = obtenerSlotPreview(colorRepeater, vista);
            if (!slot) return;

            var url = URL.createObjectURL(file);
            urls.push(url);

            var preview = slot.querySelector('.admin-upload-item__preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'admin-upload-item__preview';
                var placeholder = slot.querySelector('.admin-upload-item__placeholder');
                if (placeholder) {
                    placeholder.replaceWith(preview);
                } else {
                    slot.appendChild(preview);
                }
            }
            preview.src = url;
            preview.alt = file.name;
            slot.classList.remove('admin-upload-item--vacio');
        });

        if (urls.length) {
            previewUrls.set(input, urls);
        }

        if (sinVista.length) {
            sinVista.forEach(function (nombre) {
                var li = document.createElement('li');
                li.textContent = 'No se reconoce la vista en «' + nombre + '».';
                avisos.appendChild(li);
            });
            avisos.hidden = false;
        }
    }

    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('[data-add]');
        if (addBtn) {
            agregarRepeater(addBtn.getAttribute('data-add'));
            return;
        }

        var removeBtn = e.target.closest('[data-remove]');
        if (removeBtn) {
            var item = removeBtn.closest('[data-repeater]');
            var contenedor = item && item.parentElement;
            if (item && contenedor && contenedor.children.length > 1) {
                item.remove();
            }
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('admin-upload-lote__input')) {
            procesarLoteImagenes(e.target);
        }
    });
})();
