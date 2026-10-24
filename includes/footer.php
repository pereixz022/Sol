<?php
/**
 * NEXO WMS - Rodapé Comum e Scripts Globais
 */
?>
</div> <!-- Fim de flex-1 flex flex-col md:flex-row -->

<?php require_once __DIR__ . '/modal.php'; ?>

<script>
// Gerenciamento de Toasts
function dismissToast(el) {
    if (!el) return;
    el.classList.add('opacity-0', 'translate-x-full');
    setTimeout(() => {
        el.remove();
    }, 300);
}

// Auto-dismiss de Toasts após 4.5 segundos
document.addEventListener('DOMContentLoaded', () => {
    const toasts = document.querySelectorAll('.toast-item');
    toasts.forEach((t) => {
        setTimeout(() => {
            dismissToast(t);
        }, 4500);
    });

    // Controle de abertura da Sidebar Mobile
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('main-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        backdrop.classList.remove('hidden');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
    }

    if (mobileBtn) mobileBtn.addEventListener('click', openSidebar);
    if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // Interceptador global para botões e links com data-confirm
    // Substitui nativo confirm() por Modal Tailwind
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm');
            const title = this.getAttribute('data-title') || 'Confirmar Ação';
            const type = this.getAttribute('data-type') || 'danger';
            const btnText = this.getAttribute('data-btn-text') || 'Confirmar';
            const href = this.getAttribute('href');
            const form = this.closest('form');

            if (href && href !== '#') {
                showConfirmModal(title, message, href, type, btnText);
            } else if (form) {
                showConfirmModal(title, message, () => {
                    form.submit();
                }, type, btnText);
            }
        });
    });
});
</script>
</body>
</html>
