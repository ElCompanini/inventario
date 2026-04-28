<style>
@keyframes cc-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
@keyframes cc-modal-in {
    from { opacity:0; transform:scale(.95) translateY(-6px); }
    to   { opacity:1; transform:scale(1)  translateY(0); }
}
</style>

<div class="cc-cc-wrapper" style="margin-top:0.75rem;">
    <button type="button"
            onclick="var p=document.getElementById('cc-panel-wrap-{{ $selectId }}');var open=p.style.display==='block';p.style.display=open?'none':'block';this.querySelector('svg').style.transform=open?'':'rotate(45deg)';"
            id="cc-toggle-btn-{{ $selectId }}"
            class="btn-ghost inline-flex items-center gap-1.5 w-full px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-300 rounded-lg">
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
                    class="btn-primary px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-lg">
                Guardar
            </button>
        </div>
        {{-- Nombre completo encontrado en BD remota --}}
        <div id="cc-remota-{{ $selectId }}" style="display:none; margin-top:0.35rem;">
            <div class="flex items-start gap-1.5 px-2 py-1.5 bg-green-50 border border-green-200 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-green-700">Encontrado en base de datos</p>
                    <p id="cc-remota-nombre-{{ $selectId }}" class="text-xs text-green-800 truncate"></p>
                </div>
            </div>
        </div>
        <p id="cc-msg-{{ $selectId }}" class="text-xs mt-1 hidden"></p>
    </div>
</div>

{{-- Modal de confirmación --}}
<div id="cc-modal-{{ $selectId }}"
     style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:1rem;">
    {{-- Backdrop --}}
    <div id="cc-modal-bg-{{ $selectId }}"
         onclick="ccModalCerrar_{{ str_replace('-','_',$selectId) }}()"
         style="position:absolute; inset:0; background:rgba(15,23,42,0.45); backdrop-filter:blur(2px);"></div>
    {{-- Card --}}
    <div style="position:relative; background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.18); width:100%; max-width:380px; padding:1.5rem; animation:cc-modal-in .18s ease;">
        {{-- Encabezado --}}
        <h3 style="font-size:0.95rem; font-weight:700; color:#1e293b; margin:0 0 0.75rem;">Ingreso de centro de costo</h3>
        <hr style="border:none; border-top:1px solid #e2e8f0; margin:0 0 0.85rem;">
        <p style="font-size:0.85rem; font-weight:600; color:#334155; margin:0 0 0.3rem;">¿Desea confirmar el centro de costo?</p>
        {{-- Nombre completo --}}
        <p id="cc-modal-desc-{{ $selectId }}"
           style="font-size:0.8rem; color:#64748b; margin:0 0 0.75rem; line-height:1.45;"></p>
        {{-- Acrónimo badge --}}
        <div style="margin-bottom:1.25rem;">
            <span id="cc-modal-acronimo-{{ $selectId }}"
                  style="display:inline-block; background:#eef2ff; color:#3730a3; font-family:monospace; font-weight:700; font-size:0.9rem; padding:3px 12px; border-radius:9999px; border:1px solid #c7d2fe;"></span>
        </div>
        {{-- Botones --}}
        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button"
                    onclick="ccModalCerrar_{{ str_replace('-','_',$selectId) }}()"
                    style="padding:0.45rem 1rem; font-size:0.8rem; font-weight:600; color:#475569; background:#f1f5f9; border:none; border-radius:0.5rem; cursor:pointer;"
                    onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Cancelar
            </button>
            <button type="button"
                    id="cc-modal-ok-{{ $selectId }}"
                    style="padding:0.45rem 1.1rem; font-size:0.8rem; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:0.5rem; cursor:pointer;"
                    onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                Agregar
            </button>
        </div>
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
    var nombreCompletoActual = null;
    var pendingAcronimo      = null;

    function msg(text, color) {
        var el = document.getElementById('cc-msg-' + selectId);
        el.textContent = text;
        el.className   = 'text-xs ' + color;
        el.classList.remove('hidden');
    }

    function clearMsg() {
        document.getElementById('cc-msg-' + selectId).classList.add('hidden');
    }

    function mostrarRemota(nombreCompleto) {
        var wrap  = document.getElementById('cc-remota-' + selectId);
        var label = document.getElementById('cc-remota-nombre-' + selectId);
        nombreCompletoActual = nombreCompleto;
        if (nombreCompleto) {
            label.textContent  = nombreCompleto;
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
            nombreCompletoActual = null;
        }
    }

    function ccCerrar() {
        var panel = document.getElementById('cc-panel-wrap-' + selectId);
        var btn   = document.getElementById('cc-toggle-btn-' + selectId);
        panel.style.display = 'none';
        if (btn) btn.querySelector('svg').style.transform = '';
    }

    // ── Modal helpers ──────────────────────────────────────────────────────
    window['ccModalCerrar_' + safeId] = function() {
        var modal = document.getElementById('cc-modal-' + selectId);
        modal.style.display = 'none';
        pendingAcronimo = null;
        // re-enable save button
        document.getElementById('cc-btn-' + selectId).disabled = false;
    };

    function mostrarModal(acronimo, nombreCompleto) {
        pendingAcronimo = acronimo;
        document.getElementById('cc-modal-acronimo-' + selectId).textContent = acronimo;

        var desc = document.getElementById('cc-modal-desc-' + selectId);
        if (nombreCompleto) {
            desc.textContent  = nombreCompleto;
            desc.style.display = 'block';
        } else {
            desc.style.display = 'none';
        }

        var modal = document.getElementById('cc-modal-' + selectId);
        modal.style.display = 'flex';

        // Wire OK button (replace to avoid duplicate listeners)
        var okBtn = document.getElementById('cc-modal-ok-' + selectId);
        var nuevo = okBtn.cloneNode(true);
        okBtn.parentNode.replaceChild(nuevo, okBtn);
        nuevo.addEventListener('click', function() {
            ejecutarGuardado(pendingAcronimo);
        });
    }

    function ejecutarGuardado(acronimo) {
        document.getElementById('cc-modal-' + selectId).style.display = 'none';
        var btn = document.getElementById('cc-btn-' + selectId);
        btn.disabled = true;

        fetch(urlStore, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body:    JSON.stringify({ nombre: acronimo, nombre_completo: nombreCompletoActual || '' })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.ok) {
                var sel = document.getElementById(selectId);
                var opt = document.createElement('option');
                opt.value    = d.id;
                opt.text     = d.nombre;
                opt.selected = true;
                sel.appendChild(opt);
                document.getElementById('cc-input-' + selectId).value = '';
                clearMsg();
                mostrarRemota(null);
                ccCerrar();
            } else {
                msg(d.mensaje, 'text-orange-500');
                btn.disabled = false;
            }
        });
    }

    // ── Verificar ──────────────────────────────────────────────────────────
    window['ccVerificar_' + safeId] = function(valor) {
        clearTimeout(timer);
        var acronimo = valor.trim().toUpperCase();
        var btn      = document.getElementById('cc-btn-' + selectId);
        var msgEl    = document.getElementById('cc-msg-' + selectId);
        btn.disabled = true;
        mostrarRemota(null);

        if (!acronimo) { msgEl.classList.add('hidden'); return; }

        msgEl.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;color:#6b7280;">'
            + '<svg style="width:11px;height:11px;animation:cc-spin 0.7s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">'
            + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m6.364 1.636-2.121 2.121M21 12h-3m-1.636 6.364-2.121-2.121M12 21v-3m-6.364-1.636 2.121 2.121M3 12h3m1.636-6.364 2.121 2.121"/>'
            + '</svg>Verificando...</span>';
        msgEl.className = 'text-xs';
        msgEl.classList.remove('hidden');

        timer = setTimeout(function() {
            fetch(urlVerif + '?nombre=' + encodeURIComponent(acronimo))
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.existe) {
                        msg(d.mensaje, 'text-orange-500');
                        btn.disabled = true;
                        mostrarRemota(null);
                    } else if (d.en_remota) {
                        clearMsg();
                        mostrarRemota(d.nombre_completo);
                        btn.disabled = false;
                    } else {
                        msg(d.mensaje, 'text-gray-500');
                        btn.disabled = false;
                        mostrarRemota(null);
                    }
                });
        }, 400);
    };

    // ── Guardar (abre modal) ───────────────────────────────────────────────
    window['ccGuardar_' + safeId] = function() {
        var acronimo = document.getElementById('cc-input-' + selectId).value.trim().toUpperCase();
        if (!acronimo) return;
        mostrarModal(acronimo, nombreCompletoActual);
    };
}());
</script>
