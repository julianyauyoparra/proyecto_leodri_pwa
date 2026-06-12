(function () {
    'use strict';

    var CAMPOS = ['marca', 'nombre', 'precio', 'tallas', 'stock', 'color', 'notas'];
    var ETIQUETAS = {
        marca: 'Marca',
        nombre: 'Nombre',
        precio: 'Precio (S/)',
        tallas: 'Tallas (21,22 o 21-26)',
        stock: 'Stock (ej. 3)',
        color: 'Color (opcional)',
        notas: 'Notas (opcional)'
    };

    var lista = document.getElementById('carga-lista');
    var pegar = document.getElementById('carga-pegar');
    var resumen = document.getElementById('carga-resumen');
    var btnImportar = document.getElementById('carga-btn-importar');
    var filasJson = document.getElementById('carga-filas-json');
    var formImportar = document.getElementById('carga-form-importar');

    if (!lista) return;

    function crearFila(valores) {
        var item = document.createElement('article');
        item.className = 'admin-carga-item';
        item.setAttribute('data-fila', '');

        var cabecera = document.createElement('header');
        cabecera.className = 'admin-carga-item__cabecera';

        var titulo = document.createElement('span');
        titulo.className = 'admin-carga-item__titulo';
        titulo.textContent = 'Producto';
        cabecera.appendChild(titulo);

        var quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'admin-carga-quitar';
        quitar.textContent = 'Quitar';
        quitar.addEventListener('click', function () {
            if (lista.querySelectorAll('[data-fila]').length <= 1) {
                limpiarFila(item);
            } else {
                item.remove();
            }
            renumerarFilas();
            actualizarResumen();
        });
        cabecera.appendChild(quitar);
        item.appendChild(cabecera);

        var campos = document.createElement('div');
        campos.className = 'admin-carga-item__campos';

        CAMPOS.forEach(function (campo) {
            var label = document.createElement('label');
            label.className = 'admin-carga-campo';
            if (campo === 'nombre' || campo === 'notas') {
                label.classList.add('admin-carga-campo--full');
            }

            var span = document.createElement('span');
            span.className = 'admin-carga-campo__label';
            span.textContent = ETIQUETAS[campo];

            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'admin-carga-campo__input';
            input.setAttribute('data-campo', campo);
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('enterkeyhint', campo === 'notas' ? 'done' : 'next');
            if (campo === 'precio' || campo === 'stock') {
                input.inputMode = 'decimal';
            }
            input.value = valores && valores[campo] ? valores[campo] : '';

            label.appendChild(span);
            label.appendChild(input);
            campos.appendChild(label);
        });

        item.appendChild(campos);
        return item;
    }

    function limpiarFila(item) {
        item.querySelectorAll('input').forEach(function (input) {
            input.value = '';
        });
    }

    function renumerarFilas() {
        lista.querySelectorAll('[data-fila]').forEach(function (item, index) {
            var titulo = item.querySelector('.admin-carga-item__titulo');
            if (titulo) {
                titulo.textContent = 'Producto ' + (index + 1);
            }
        });
    }

    function leerFilas() {
        var filas = [];
        lista.querySelectorAll('[data-fila]').forEach(function (item) {
            var datos = {};
            var vacia = true;
            CAMPOS.forEach(function (campo) {
                var input = item.querySelector('[data-campo="' + campo + '"]');
                var valor = input ? input.value.trim() : '';
                datos[campo] = valor;
                if (valor !== '') vacia = false;
            });
            if (!vacia) filas.push(datos);
        });
        return filas;
    }

    function actualizarResumen() {
        var filas = leerFilas();
        var n = filas.length;
        resumen.textContent = n === 1 ? '1 producto listo' : n + ' productos listos';
        btnImportar.disabled = n === 0;
        filasJson.value = JSON.stringify(filas);
    }

    function parsearTexto(texto) {
        var lineas = texto.split(/\r\n|\n|\r/).map(function (l) { return l.trim(); }).filter(Boolean);
        var filas = [];

        lineas.forEach(function (linea) {
            var cols;
            if (linea.indexOf('\t') >= 0) {
                cols = linea.split('\t');
            } else if ((linea.match(/;/g) || []).length > (linea.match(/,/g) || []).length) {
                cols = linea.split(';');
            } else {
                cols = linea.split(',');
            }

            var unido = cols.join(' ').toLowerCase();
            if (unido.indexOf('marca') >= 0 && unido.indexOf('nombre') >= 0) {
                return;
            }

            var item = {};
            CAMPOS.forEach(function (campo, i) {
                item[campo] = (cols[i] || '').trim();
            });

            if (item.marca === '' && item.nombre === '') return;
            filas.push(item);
        });

        return filas;
    }

    function agregarFilasDesdeDatos(datos) {
        if (!datos.length) return;

        var primera = lista.querySelector('[data-fila]');
        var primeraVacia = true;
        if (primera) {
            primeraVacia = Array.prototype.every.call(primera.querySelectorAll('input'), function (i) {
                return i.value.trim() === '';
            });
        }

        datos.forEach(function (item, index) {
            if (index === 0 && primeraVacia && primera) {
                CAMPOS.forEach(function (campo) {
                    var input = primera.querySelector('[data-campo="' + campo + '"]');
                    if (input) input.value = item[campo] || '';
                });
            } else {
                lista.appendChild(crearFila(item));
            }
        });

        renumerarFilas();
        actualizarResumen();
    }

    document.getElementById('carga-btn-fila').addEventListener('click', function () {
        lista.appendChild(crearFila(null));
        renumerarFilas();
        actualizarResumen();
        var ultimo = lista.lastElementChild;
        if (ultimo) {
            var primerInput = ultimo.querySelector('input');
            if (primerInput) primerInput.focus();
        }
    });

    document.getElementById('carga-btn-pegar').addEventListener('click', function () {
        var texto = pegar.value.trim();
        if (!texto) return;
        agregarFilasDesdeDatos(parsearTexto(texto));
        pegar.value = '';
    });

    document.getElementById('carga-archivo').addEventListener('change', function (ev) {
        var file = ev.target.files && ev.target.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function () {
            agregarFilasDesdeDatos(parsearTexto(String(reader.result || '')));
            ev.target.value = '';
        };
        reader.readAsText(file, 'UTF-8');
    });

    lista.addEventListener('input', actualizarResumen);

    formImportar.addEventListener('submit', function (ev) {
        actualizarResumen();
        if (btnImportar.disabled) {
            ev.preventDefault();
        }
    });

    lista.appendChild(crearFila(null));
    renumerarFilas();
    actualizarResumen();
})();
