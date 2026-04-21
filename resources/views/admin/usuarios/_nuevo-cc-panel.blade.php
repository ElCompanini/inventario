<style>
@keyframes cc-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.cc-panel-open {
    display: block !important;
}
</style>

<div class="cc-cc-wrapper" style="margin-top:0.75rem;">
    <button type="button"
            onclick="var p=document.getElementById('cc-panel-wrap-{{ $selectId }}');var open=p.style.display==='block';p.style.display=open?'none':'block';this.querySelector('svg').style.transform=open?'':'rotate(45deg)';"
            id="cc-toggle-btn-{{ $selectId }}"
            class="inline-flex items-center gap-1.5 w-full px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-300 rounded-lg transition-colors duration-150">
        <svg class="w-3 h-3 flex-shrink-0" style="transition:transform 0.2s ease;"
             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo centro de costo
    </button>

    <div class="cc-panel" id="cc-panel-wrap-{{ $selectId }}" style="display:none; margin-top:0.375rem;">
        <div class="flex gap-2">
            <input type="text" id="cc-input-{{ $selectId }}"
                   placeholder="Ej: TIC"
                   maxlength="50"
                   style="text-transform:uppercase"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400"
                   oninput="ccVerificar_{{ str_replace('-','_',$selectId) }}(this.value)">
            <button type="button"
                    id="cc-btn-{{ $selectId }}"
                    disabled
                    onclick="ccGuardar_{{ str_replace('-','_',$selectId) }}()"
                    class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-lg transition">
                Guardar
            </button>
        </div>
        <p id="cc-msg-{{ $selectId }}" class="text-xs mt-1 hidden"></p>
    </div>
</div>

<script>
(function() {
    var selectId = {!! json_encode($selectId, JSON_HEX_TAG | JSON_HEX_AMP) !!};
    var safeId   = selectId.replace(/-/g, '_');
    var urlVerif = {!! json_encode(route('admin.dev.centros-costo.verificar'), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    var urlStore = {!! json_encode(route('admin.dev.centros-costo.store'), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    var csrf     = {!! json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    var timer    = null;

    function msg(text, color) {
        var el = document.getElementById('cc-msg-' + selectId);
        el.textContent = text;
        el.className   = 'text-xs ' + color;
        el.classList.remove('hidden');
    }

    function ccCerrar() {
        var panel = document.getElementById('cc-panel-wrap-' + selectId);
        var btn   = document.getElementById('cc-toggle-btn-' + selectId);
        panel.style.display = 'none';
        if (btn) btn.querySelector('svg').style.transform = '';
    }

    window['ccVerificar_' + safeId] = function(valor) {
        clearTimeout(timer);
        var nombre = valor.trim().toUpperCase();
        var btn    = document.getElementById('cc-btn-' + selectId);
        var msgEl  = document.getElementById('cc-msg-' + selectId);
        btn.disabled = true;
        if (!nombre) {
            msgEl.classList.add('hidden');
            return;
        }
        msgEl.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;color:#6b7280;">'
            + '<svg style="width:11px;height:11px;animation:cc-spin 0.7s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">'
            + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m6.364 1.636-2.121 2.121M21 12h-3m-1.636 6.364-2.121-2.121M12 21v-3m-6.364-1.636 2.121-2.121M3 12h3m1.636-6.364 2.121 2.121"/>'
            + '</svg>Verificando...</span>';
        msgEl.className = 'text-xs';
        msgEl.classList.remove('hidden');
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
