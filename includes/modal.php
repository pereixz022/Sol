<?php
/**
 * NEXO WMS - Sistema de Modais Reutilizáveis
 * Substitui caixas de diálogo nativas do navegador (alert e confirm)
 */
?>

<!-- Modal Global de Confirmação & Alertas -->
<div id="app-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="app-modal-card" class="bg-slate-900 border border-slate-700/80 rounded-2xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-transform duration-200 text-slate-100">
        
        <!-- Ícone e Cabeçalho do Modal -->
        <div class="flex items-start gap-4">
            <div id="app-modal-icon-container" class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-cyan-950 border border-cyan-800 text-cyan-400">
                <i id="app-modal-icon" class="fa-solid fa-circle-question text-xl"></i>
            </div>
            <div class="flex-1">
                <h3 id="app-modal-title" class="text-lg font-bold text-white tracking-tight">Confirmação</h3>
                <p id="app-modal-message" class="text-sm text-slate-300 mt-1 leading-relaxed">Você tem certeza que deseja executar esta ação?</p>
            </div>
        </div>

        <!-- Botões de Ação do Modal -->
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3">
            <button id="app-modal-cancel" type="button" onclick="closeAppModal()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white transition-colors border border-slate-700">
                Cancelar
            </button>
            <a id="app-modal-confirm" href="#" class="w-full sm:w-auto text-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-cyan-600 hover:bg-cyan-500 shadow-lg shadow-cyan-600/30 transition-all">
                Confirmar
            </a>
        </div>
    </div>
</div>

<script>
/**
 * Abre o Modal Global de Confirmação ou Alerta
 * @param {string} title Título do modal
 * @param {string} message Mensagem descritiva
 * @param {string|function} actionUrlOrCallback URL de destino ou função callback
 * @param {string} type 'danger' | 'warning' | 'primary' | 'alert'
 * @param {string} confirmText Texto do botão de confirmação
 */
function showConfirmModal(title, message, actionUrlOrCallback, type = 'primary', confirmText = 'Confirmar') {
    const modal = document.getElementById('app-modal');
    const card = document.getElementById('app-modal-card');
    const titleEl = document.getElementById('app-modal-title');
    const msgEl = document.getElementById('app-modal-message');
    const iconContainer = document.getElementById('app-modal-icon-container');
    const icon = document.getElementById('app-modal-icon');
    const confirmBtn = document.getElementById('app-modal-confirm');
    const cancelBtn = document.getElementById('app-modal-cancel');

    titleEl.textContent = title;
    msgEl.textContent = message;
    confirmBtn.textContent = confirmText;

    // Reseta classes do botão e do ícone
    confirmBtn.className = 'w-full sm:w-auto text-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all shadow-lg';
    iconContainer.className = 'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 border';

    if (type === 'danger') {
        iconContainer.classList.add('bg-rose-950/80', 'border-rose-700', 'text-rose-400');
        icon.className = 'fa-solid fa-triangle-exclamation text-xl';
        confirmBtn.classList.add('bg-rose-600', 'hover:bg-rose-500', 'shadow-rose-600/30');
    } else if (type === 'warning') {
        iconContainer.classList.add('bg-amber-950/80', 'border-amber-700', 'text-amber-400');
        icon.className = 'fa-solid fa-circle-exclamation text-xl';
        confirmBtn.classList.add('bg-amber-600', 'hover:bg-amber-500', 'shadow-amber-600/30');
    } else if (type === 'alert') {
        iconContainer.classList.add('bg-blue-950/80', 'border-blue-700', 'text-blue-400');
        icon.className = 'fa-solid fa-circle-info text-xl';
        confirmBtn.classList.add('bg-blue-600', 'hover:bg-blue-500', 'shadow-blue-600/30');
        cancelBtn.classList.add('hidden');
    } else {
        iconContainer.classList.add('bg-cyan-950/80', 'border-cyan-700', 'text-cyan-400');
        icon.className = 'fa-solid fa-circle-question text-xl';
        confirmBtn.classList.add('bg-cyan-600', 'hover:bg-cyan-500', 'shadow-cyan-600/30');
    }

    if (type !== 'alert') {
        cancelBtn.classList.remove('hidden');
    }

    // Configura ação
    if (typeof actionUrlOrCallback === 'function') {
        confirmBtn.removeAttribute('href');
        confirmBtn.onclick = function(e) {
            e.preventDefault();
            closeAppModal();
            actionUrlOrCallback();
        };
    } else if (typeof actionUrlOrCallback === 'string') {
        confirmBtn.onclick = null;
        confirmBtn.href = actionUrlOrCallback;
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 10);
}

function closeAppModal() {
    const modal = document.getElementById('app-modal');
    const card = document.getElementById('app-modal-card');
    if (!modal) return;
    modal.classList.add('opacity-0');
    card.classList.remove('scale-100');
    card.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Fechar com tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAppModal();
    }
});
</script>
