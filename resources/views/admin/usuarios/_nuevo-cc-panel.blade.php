<div class="mt-3 cc-cc-wrapper">
    <button type="button"
            onclick="var w=this.closest('.cc-cc-wrapper');w.querySelector('.cc-panel').classList.toggle('cc-panel-open');w.querySelector('svg').style.transform=w.querySelector('.cc-panel').classList.contains('cc-panel-open')?'rotate(45deg)':'';"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-300 rounded-lg">
        <svg class="w-3.5 h-3.5" style="transition:transform 0.2s ease;"
             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo centro de costo
    </button>

    <div class="cc-panel">
        <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
            <div class="flex gap-2">
                <input type="text" id="cc-input-{{ $selectId }}"
                       placeholder="Ej: TIC"
                       maxlength="50"
                       style="text-transform:uppercase"
                       class="flex-1 border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400"
                       oninput="ccVerificar_{{ str_replace('-','_',$selectId) }}(this.value)">
                <button type="button"
                        id="cc-btn-{{ $selectId }}"
                        disabled
                        onclick="ccGuardar_{{ str_replace('-','_',$selectId) }}()"
                        class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-lg transition">
                    Guardar
                </button>
            </div>
            <p id="cc-msg-{{ $selectId }}" class="text-xs hidden"></p>
        </div>
    </div>
</div>

<style>
.cc-panel {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    margin-top: 0.5rem;
    transition: max-height 0.3s ease, opacity 0.25s ease;
}
.cc-panel.cc-panel-open {
    max-height: 200px;
    opacity: 1;
}
</style>

<script>
(function() {
    var selectId = {!! json_encode($selectId) !!};
    var safeId   = selectId.replace(/-/g, '_');
    var urlVerif = {!! json_encode(route('admin.dev.centros-costo.verificar')) !!};
    var urlStore = {!! json_encode(route('admin.dev.centros-costo.store')) !!};
    var csrf     = {!! json_encode(csrf_token()) !!};
    var timer    = null;

    function msg(text, color) {
        var el = document.getElementById('cc-msg-' + selectId);
        el.textContent = text;
        el.className   = 'text-xs ' + color;
        el.classList.remove('hidden');
    }

    function ccCerrar() {
        var input = document.getElementById('cc-input-' + selectId);
        var panel = input.closest('.cc-cc-wrapper').querySelector('.cc-panel');
        var svg   = input.closest('.cc-cc-wrapper').querySelector('svg');
        panel.classList.remove('cc-panel-open');
        svg.style.transform = '';
    }

    window['ccVerificar_' + safeId] = function(valor) {
        clearTimeout(timer);
        var nombre = valor.trim().toUpperCase();
        var btn    = document.getElementById('cc-btn-' + selectId);
        btn.disabled = true;
        if (!nombre) {
            document.getElementById('cc-msg-' + selectId).classList.add('hidden');
            return;
        }
        timer = setTimeout(function() {
            fetch(urlVerif + '?nombre=' + encodeURIComponent(nombre))
                .then(function(r){ return r.json(); })
                .then(function(d){
                    msg(d.mensaje, d.existe ? 'text-orange-500' : 'text-green-600');
                    btn.disabled = d.existe;
                });
        }, 400);
    };

    window['ccGuardar_' + safeId] = function() {
        var nombre = document.getElementById('cc-input-' + selectId).value.trim().toUpperCase();
        if (!nombre) return;
        if (!confirm('¿Desea crear el centro de costo "' + nombre + '"?')) return;

        var btn = document.getElementById('cc-btn-' + selectId);
        btn.disabled = true;

        fetch(urlStore, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ nombre: nombre })
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            msg(d.mensaje, d.ok ? 'text-green-600' : 'text-orange-500');
            if (d.ok) {
                var sel = document.getElementById(selectId);
                var opt = document.createElement('option');
                opt.value    = d.nombre;
                opt.text     = d.nombre;
                opt.selected = true;
                sel.appendChild(opt);
                document.getElementById('cc-input-' + selectId).value = '';
                document.getElementById('cc-msg-' + selectId).classList.add('hidden');
                ccCerrar();
            } else {
                btn.disabled = false;
            }
        });
    };
}());
</script>
