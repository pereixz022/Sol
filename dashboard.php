<?php
/**
 * NEXO WMS - Painel de Controle Principal (Dashboard)
 */
define('PAGE_TITLE', 'Dashboard Geral');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user = currentUser();
$isGest = isGestor();

try {
    // 1. Estatísticas Gerais de Estoque
    $stmtGeral = $pdo->query("
        SELECT 
            COUNT(*) as total_produtos,
            COALESCE(SUM(quantidade), 0) as total_itens,
            COALESCE(SUM(quantidade * preco), 0) as valor_total,
            SUM(CASE WHEN quantidade <= estoque_minimo THEN 1 ELSE 0 END) as produtos_alerta,
            SUM(CASE WHEN quantidade = 0 THEN 1 ELSE 0 END) as produtos_zerados
        FROM produtos 
        WHERE status = 'ativo'
    ");
    $stats = $stmtGeral->fetch();

    // 2. Movimentações Recentes (últimas 6)
    $stmtMov = $pdo->query("
        SELECT 
            m.*, 
            p.nome as produto_nome, 
            p.sku as produto_sku, 
            p.unidade as produto_unidade,
            u.nome as usuario_nome
        FROM movimentacoes m
        JOIN produtos p ON m.produto_id = p.id
        JOIN usuarios u ON m.usuario_id = u.id
        ORDER BY m.id DESC
        LIMIT 6
    ");
    $recentes = $stmtMov->fetchAll();

    // 3. Produtos em Situação Crítica (Estoque Baixo)
    $stmtAlertas = $pdo->query("
        SELECT 
            p.*, 
            c.nome as categoria_nome
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.status = 'ativo' AND p.quantidade <= p.estoque_minimo
        ORDER BY p.quantidade ASC
        LIMIT 5
    ");
    $produtosAlerta = $stmtAlertas->fetchAll();

    // 4. Totais de Entradas e Saídas
    $stmtTotaisMov = $pdo->query("
        SELECT 
            tipo, 
            COUNT(*) as qtd_movs, 
            COALESCE(SUM(quantidade), 0) as volume_itens 
        FROM movimentacoes 
        GROUP BY tipo
    ");
    $totaisMov = ['entrada' => ['qtd' => 0, 'itens' => 0], 'saida' => ['qtd' => 0, 'itens' => 0]];
    while ($row = $stmtTotaisMov->fetch()) {
        $totaisMov[$row['tipo']] = ['qtd' => (int)$row['qtd_movs'], 'itens' => (int)$row['volume_itens']];
    }

} catch (PDOException $e) {
    setToast('error', 'Erro ao carregar dados do dashboard.');
    $stats = ['total_produtos' => 0, 'total_itens' => 0, 'valor_total' => 0, 'produtos_alerta' => 0, 'produtos_zerados' => 0];
    $recentes = [];
    $produtosAlerta = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Área de Conteúdo Principal -->
<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">
    
    <!-- Banner de Boas-Vindas com Ações Rápidas -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl mb-8">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider <?= $isGest ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-cyan-950 text-cyan-300 border border-cyan-800' ?>">
                        <?= $isGest ? 'Painel do Gestor' : 'Painel Operacional' ?>
                    </span>
                    <span class="text-xs text-slate-400">Armazém Central NEXO</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Olá, <span class="text-cyan-400"><?= htmlspecialchars($user['nome']) ?></span>! 👋
                </h1>
                <p class="text-sm text-slate-400 mt-1 max-w-xl">
                    <?= $isGest ? 'Acompanhe em tempo real os indicadores de estoque, movimentações de carga e alertas do armazém.' : 'Consulte os produtos do estoque e registre movimentações de entrada e saída conforme suas permissões.' ?>
                </p>
            </div>

            <!-- Botões de Ação Rápida -->
            <div class="flex flex-wrap items-center gap-3">
                <?php if (canRegisterEntrada()): ?>
                    <a href="movimentacao.php?tipo=entrada" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/25 transition-all">
                        <i class="fa-solid fa-arrow-down"></i>
                        <span>Entrada de Carga</span>
                    </a>
                <?php endif; ?>

                <?php if (canRegisterSaida()): ?>
                    <a href="movimentacao.php?tipo=saida" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/25 transition-all">
                        <i class="fa-solid fa-arrow-up"></i>
                        <span>Saída / Separação</span>
                    </a>
                <?php endif; ?>

                <?php if ($isGest): ?>
                    <a href="produto_form.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-purple-200 bg-purple-950/80 border border-purple-800 hover:bg-purple-900/60 transition-all">
                        <i class="fa-solid fa-plus"></i>
                        <span>Novo Produto</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grade de Indicadores (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        
        <!-- KPI 1: Total de Itens -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total em Estoque</span>
                <div class="w-10 h-10 rounded-xl bg-cyan-950 border border-cyan-800/80 flex items-center justify-center text-cyan-400 text-lg">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">
                    <?= number_format($stats['total_itens'], 0, ',', '.') ?>
                </span>
                <span class="text-xs text-slate-400 ml-1">unidades</span>
            </div>
            <p class="mt-2 text-xs text-slate-400">
                Distribuídos em <strong class="text-slate-200"><?= $stats['total_produtos'] ?></strong> itens cadastrados
            </p>
        </div>

        <!-- KPI 2: Alertas de Reposição -->
        <div class="bg-slate-900 border <?= $stats['produtos_alerta'] > 0 ? 'border-amber-700/60 bg-amber-950/10' : 'border-slate-800' ?> rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider <?= $stats['produtos_alerta'] > 0 ? 'text-amber-400' : 'text-slate-400' ?>">
                    Estoque Baixo
                </span>
                <div class="w-10 h-10 rounded-xl <?= $stats['produtos_alerta'] > 0 ? 'bg-amber-950 border border-amber-800 text-amber-400 animate-pulse' : 'bg-slate-800 text-slate-400' ?> flex items-center justify-center text-lg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold <?= $stats['produtos_alerta'] > 0 ? 'text-amber-300' : 'text-white' ?> tracking-tight">
                    <?= (int)$stats['produtos_alerta'] ?>
                </span>
                <span class="text-xs text-slate-400 ml-1">produtos</span>
            </div>
            <p class="mt-2 text-xs text-slate-400">
                <?= $stats['produtos_zerados'] > 0 ? "<span class='text-rose-400 font-bold'>{$stats['produtos_zerados']} zerados</span> no momento" : 'Nenhum item zerado' ?>
            </p>
        </div>

        <!-- KPI 3: Volume de Entradas -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total de Entradas</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-950 border border-emerald-800/80 flex items-center justify-center text-emerald-400 text-lg">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">
                    <?= number_format($totaisMov['entrada']['itens'], 0, ',', '.') ?>
                </span>
                <span class="text-xs text-slate-400 ml-1">itens</span>
            </div>
            <p class="mt-2 text-xs text-slate-400">
                Registrados em <strong class="text-emerald-400"><?= $totaisMov['entrada']['qtd'] ?></strong> operações
            </p>
        </div>

        <!-- KPI 4: Volume de Saídas / Expedição -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total de Saídas</span>
                <div class="w-10 h-10 rounded-xl bg-blue-950 border border-blue-800/80 flex items-center justify-center text-blue-400 text-lg">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">
                    <?= number_format($totaisMov['saida']['itens'], 0, ',', '.') ?>
                </span>
                <span class="text-xs text-slate-400 ml-1">itens</span>
            </div>
            <p class="mt-2 text-xs text-slate-400">
                Registrados em <strong class="text-blue-400"><?= $totaisMov['saida']['qtd'] ?></strong> operações
            </p>
        </div>
    </div>

    <!-- Seção Dividida: Alertas de Reposição e Últimas Movimentações -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Coluna Esquerda: Itens Críticos que precisam de atenção (1 col no LG) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-950 text-amber-400 border border-amber-800/80 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white tracking-tight">Alerta de Reposição</h2>
                        <p class="text-[11px] text-slate-400">Estoque igual ou abaixo do mínimo</p>
                    </div>
                </div>
                <a href="estoque.php?filtro=alerta" class="text-xs text-cyan-400 hover:text-cyan-300 font-semibold">Ver todos</a>
            </div>

            <div class="space-y-3 flex-1">
                <?php if (empty($produtosAlerta)): ?>
                    <div class="h-40 flex flex-col items-center justify-center text-center p-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-950/60 border border-emerald-800/60 text-emerald-400 flex items-center justify-center mb-2">
                            <i class="fa-solid fa-check text-lg"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-200">Estoque Saudável!</p>
                        <p class="text-xs text-slate-400 mt-0.5">Todos os produtos estão com níveis adequados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($produtosAlerta as $item): ?>
                        <div class="p-3.5 rounded-xl border <?= $item['quantidade'] == 0 ? 'bg-rose-950/30 border-rose-800/60' : 'bg-slate-950/60 border-slate-800' ?> flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-bold"><?= htmlspecialchars($item['sku']) ?></span>
                                    <span class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($item['categoria_nome'] ?? 'Geral') ?></span>
                                </div>
                                <h3 class="text-xs font-bold text-white mt-1 truncate" title="<?= htmlspecialchars($item['nome']) ?>">
                                    <?= htmlspecialchars($item['nome']) ?>
                                </h3>
                                <p class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-slate-500"></i>
                                    <?= htmlspecialchars($item['localizacao']) ?>
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-sm font-black <?= $item['quantidade'] == 0 ? 'text-rose-400' : 'text-amber-400' ?>">
                                    <?= $item['quantidade'] ?> <?= htmlspecialchars($item['unidade']) ?>
                                </span>
                                <p class="text-[10px] text-slate-500">Mín: <?= $item['estoque_minimo'] ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna Direita: Tabela de Últimas Movimentações (2 cols no LG) -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-cyan-950 text-cyan-400 border border-cyan-800/80 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white tracking-tight">Movimentações Recentes</h2>
                        <p class="text-[11px] text-slate-400">Últimos registros de entrada e saída do armazém</p>
                    </div>
                </div>
                <a href="historico.php" class="text-xs text-cyan-400 hover:text-cyan-300 font-semibold">Histórico Completo &rarr;</a>
            </div>

            <?php if (empty($recentes)): ?>
                <div class="py-12 text-center">
                    <i class="fa-solid fa-inbox text-3xl text-slate-600 mb-2"></i>
                    <p class="text-sm text-slate-400">Nenhuma movimentação registrada até o momento.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto -mx-5 px-5">
                    <table class="min-w-full text-left text-xs whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="pb-3 pr-4">Tipo</th>
                                <th class="pb-3 px-4">Produto</th>
                                <th class="pb-3 px-4 text-center">Quantidade</th>
                                <th class="pb-3 px-4">Motivo / Doc</th>
                                <th class="pb-3 px-4">Operador</th>
                                <th class="pb-3 pl-4 text-right">Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($recentes as $m): ?>
                                <?php $isEntrada = $m['tipo'] === 'entrada'; ?>
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold text-[10px] uppercase <?= $isEntrada ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-blue-950 text-blue-300 border border-blue-800' ?>">
                                            <i class="fa-solid <?= $isEntrada ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                                            <?= $isEntrada ? 'Entrada' : 'Saída' ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-white"><?= htmlspecialchars($m['produto_nome']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($m['produto_sku']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold">
                                        <span class="text-xs <?= $isEntrada ? 'text-emerald-400' : 'text-blue-400' ?>">
                                            <?= $isEntrada ? '+' : '-' ?><?= $m['quantidade'] ?> <?= htmlspecialchars($m['produto_unidade']) ?>
                                        </span>
                                        <div class="text-[10px] text-slate-500 font-normal">Saldo: <?= $m['saldo_posterior'] ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-slate-200"><?= htmlspecialchars($m['motivo']) ?></div>
                                        <?php if (!empty($m['documento'])): ?>
                                            <div class="text-[10px] text-slate-400 font-mono"><i class="fa-solid fa-file-lines mr-1"></i><?= htmlspecialchars($m['documento']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-300">
                                        <i class="fa-regular fa-user text-slate-500 mr-1"></i><?= htmlspecialchars($m['usuario_nome']) ?>
                                    </td>
                                    <td class="py-3 pl-4 text-right text-slate-400">
                                        <?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
