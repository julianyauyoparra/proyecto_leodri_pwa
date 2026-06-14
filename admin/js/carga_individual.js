(function () {
    var tipoLote = document.querySelector('[data-tipo-lote]');
    var mediaWrap = document.querySelector('[data-media-wrap]');
    if (tipoLote && mediaWrap) {
        function syncMedia() {
            var esMedia = tipoLote.value === 'media_docena';
            mediaWrap.hidden = !esMedia;
        }
        tipoLote.addEventListener('change', syncMedia);
        syncMedia();
    }

    var addBtn = document.querySelector('[data-add-especial]');
    var lista = document.querySelector('[data-especiales-lista]');
    var tpl = document.getElementById('tpl-especial');
    if (addBtn && lista && tpl) {
        addBtn.addEventListener('click', function () {
            lista.appendChild(tpl.content.cloneNode(true));
        });
        lista.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove-especial]');
            if (btn) {
                btn.closest('.admin-carga-individual__especial-fila')?.remove();
            }
        });
    }
})();
