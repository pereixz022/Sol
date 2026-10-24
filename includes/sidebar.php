<?php
/**
 * NEXO WMS - Sidebar e Barra de Navegação Responsiva (Mobile-First)
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
$isGest = isGestor();
$canEntrada = canRegisterEntrada();
$canSaida = canRegisterSaida();
?>

<!-- Barra de Topo no Mobile (md:hidden) -->
<header class="md:hidden bg-slate-900 border-b border-slate-800 px-4 py-3 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center gap-3">
        <button id="mobile-menu-btn" type="button" class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-cyan-500">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center font-black text-white text-sm shadow-lg shadow-cyan-500/20">
                <i class="fa-solid fa-warehouse"></i>
            </div>
            <span class="font-extrabold tracking-tight text-white text-lg">NEXO<span class="text-cyan-400">WMS</span></span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-xs px-2.5 py-1 rounded-full font-bold uppercase tracking-wider <?= $isGest ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-cyan-950 text-cyan-300 border border-cyan-800' ?>">
            <?= $isGest ? 'Gestor' : 'Funcionário' ?>
        </span>
    </div>
</header>

<!-- Backdrop Overlay para Mobile -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

<!-- Sidebar Principal (Drawer no Mobile, Fixo no Desktop) -->
<aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 border-r border-slate-800 flex flex-col transform -translate-x-full md:translate-x-0 md:static md:z-0 transition-transform duration-300 ease-in-out">
    
    <!-- Topo da Sidebar: Logotipo -->
    <div class="p-5 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 via-blue-600 to-indigo-600 flex items-center justify-center font-black text-white text-lg shadow-lg shadow-cyan-500/25">
                <i class="fa-solid fa-warehouse"></i>
            </div>
            <div>
                <h1 class="font-black text-xl tracking-tight text-white flex items-center gap-1">
                    NEXO<span class="text-cyan-400">WMS</span>
                </h1>
                <p class="text-[11px] text-slate-400 font-medium">Gestão Inteligente de Armazém</p>
            </div>
        </div>
        <button id="close-sidebar-btn" class="md:hidden text-slate-400 hover:text-white p-1">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Mini Perfil do Usuário Logado -->
    <div class="p-4 mx-3 my-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full <?= $isGest ? 'bg-purple-600' : 'bg-cyan-600' ?> flex items-center justify-center text-white font-bold text-sm shadow-md">
                <?= strtoupper(substr($user['nome'] ?? 'U', 0, 2)) ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($user['nome'] ?? '') ?></p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide <?= $isGest ? 'bg-purple-900/70 text-purple-300 border border-purple-700/50' : 'bg-cyan-900/70 text-cyan-300 border border-cyan-700/50' ?>">
                        <?= $isGest ? 'Gestor' : 'Funcionário' ?>
                    </span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[10px] text-emerald-400 font-semibold">Online</span>
                </div>
            </div>
        </div>
        <!-- Permissões operacionais do usuário -->
        <div class="mt-2.5 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
            <span class="text-slate-400">Permissões:</span>
            <div class="flex items-center gap-1.5">
                <span class="px-1.5 py-0.5 rounded <?= $canEntrada ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950/60 text-rose-400 border border-rose-900/50' ?>" title="Permissão de Entrada">
                    <i class="fa-solid fa-arrow-down mr-0.5"></i> Entrada
                </span>
                <span class="px-1.5 py-0.5 rounded <?= $canSaida ? 'bg-blue-950 text-blue-300 border border-blue-800' : 'bg-rose-950/60 text-rose-400 border border-rose-900/50' ?>" title="Permissão de Saída">
                    <i class="fa-solid fa-arrow-up mr-0.5"></i> Saída
                </span>
            </div>
        </div>
    </div>

    <!-- Navegação de Menus -->
    <nav class="flex-1 px-3 py-2 space-y-6 overflow-y-auto">
        <!-- Grupo 1: Operações Gerais -->
        <div>
            <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Visão Geral</p>
            <div class="space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'dashboard.php' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-cyan-400"></i>
                    <span>Dashboard</span>
                </a>
                <a href="estoque.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'estoque.php' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                    <i class="fa-solid fa-boxes-stacked w-5 text-center text-cyan-400"></i>
                    <span>Consulta de Estoque</span>
                </a>
                <a href="produtos.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'produtos.php' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                    <i class="fa-solid fa-box-open w-5 text-center text-cyan-400"></i>
                    <span>Catálogo de Produtos</span>
                </a>
            </div>
        </div>

        <!-- Grupo 2: Movimentações -->
        <div>
            <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Movimentações</p>
            <div class="space-y-1">
                <?php if ($canEntrada): ?>
                    <a href="movimentacao.php?tipo=entrada" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= ($currentPage === 'movimentacao.php' && ($_GET['tipo'] ?? '') === 'entrada') ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                        <i class="fa-solid fa-circle-arrow-down w-5 text-center text-emerald-400"></i>
                        <span>Registrar Entrada</span>
                    </a>
                <?php else: ?>
                    <div class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-slate-500 cursor-not-allowed opacity-60" title="Sem permissão de entrada">
                        <span class="flex items-center gap-3">
                            <i class="fa-solid fa-circle-arrow-down w-5 text-center"></i>
                            <span>Registrar Entrada</span>
                        </span>
                        <i class="fa-solid fa-lock text-xs text-slate-600"></i>
                    </div>
                <?php endif; ?>

                <?php if ($canSaida): ?>
                    <a href="movimentacao.php?tipo=saida" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= ($currentPage === 'movimentacao.php' && ($_GET['tipo'] ?? '') === 'saida') ? 'bg-blue-600/20 text-blue-300 border border-blue-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                        <i class="fa-solid fa-circle-arrow-up w-5 text-center text-blue-400"></i>
                        <span>Registrar Saída</span>
                    </a>
                <?php else: ?>
                    <div class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-slate-500 cursor-not-allowed opacity-60" title="Sem permissão de saída">
                        <span class="flex items-center gap-3">
                            <i class="fa-solid fa-circle-arrow-up w-5 text-center"></i>
                            <span>Registrar Saída</span>
                        </span>
                        <i class="fa-solid fa-lock text-xs text-slate-600"></i>
                    </div>
                <?php endif; ?>

                <a href="historico.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'historico.php' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                    <i class="fa-solid fa-clock-rotate-left w-5 text-center text-cyan-400"></i>
                    <span>Histórico Completo</span>
                </a>
            </div>
        </div>

        <!-- Grupo 3: Apenas para Gestores -->
        <?php if ($isGest): ?>
            <div>
                <div class="px-3 flex items-center justify-between mb-2">
                    <p class="text-[11px] font-bold text-purple-400 uppercase tracking-wider">Gestão Administrativa</p>
                    <span class="text-[9px] bg-purple-950 text-purple-300 px-1.5 py-0.5 rounded border border-purple-800 font-bold">Gestor</span>
                </div>
                <div class="space-y-1">
                    <a href="produto_form.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'produto_form.php' ? 'bg-purple-600/20 text-purple-300 border border-purple-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                        <i class="fa-solid fa-plus-circle w-5 text-center text-purple-400"></i>
                        <span>Novo Produto</span>
                    </a>
                    <a href="usuarios.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= in_array($currentPage, ['usuarios.php', 'usuario_form.php']) ? 'bg-purple-600/20 text-purple-300 border border-purple-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                        <i class="fa-solid fa-users-gear w-5 text-center text-purple-400"></i>
                        <span>Usuários & Permissões</span>
                    </a>
                    <a href="relatorios.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $currentPage === 'relatorios.php' ? 'bg-purple-600/20 text-purple-300 border border-purple-500/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                        <i class="fa-solid fa-file-invoice w-5 text-center text-purple-400"></i>
                        <span>Relatórios & KPIs</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Rodapé da Sidebar: Status do Banco e Logout -->
    <div class="p-3 border-t border-slate-800 space-y-2">
        <div class="px-2 py-1.5 rounded-lg bg-slate-950/40 flex items-center justify-between text-[11px] text-slate-400">
            <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-database text-xs text-cyan-400"></i>
                <span>Banco:</span>
            </span>
            <span class="font-mono font-bold uppercase text-cyan-300">
                <?= htmlspecialchars($GLOBALS['DB_DRIVER'] ?? 'PDO') ?>
            </span>
        </div>

        <button type="button" 
                onclick="showConfirmModal('Encerrar Sessão', 'Deseja realmente desconectar-se do sistema NEXO WMS?', 'logout.php', 'danger', 'Sair do Sistema')" 
                class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-400 bg-rose-950/30 border border-rose-900/40 hover:bg-rose-900/50 hover:text-rose-200 transition-colors">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Encerrar Sessão</span>
        </button>
    </div>
</aside>
