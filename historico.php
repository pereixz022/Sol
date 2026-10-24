<?php
/**
 * NEXO WMS - Histórico de Movimentações de Mercadorias
 */
define('PAGE_TITLE', 'Histórico de Movimentações');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$tipoFiltro   = trim($_GET['tipo'] ?? '');
$busca        = trim($_GET['busca'] ?? '');
$dataInicio   = trim($_GET['data_inicio'] ?? '');
$dataFim      = trim($_GET['data_fim'] ?? '');

$sql = "
    SELECT 
        m.*, 
        p.nome as produto_nome, 
        p.sku as produto_sku, 
        p.unidade as produto_unidade,
        p.localizacao as produto_local,
        u.nome as usuario_nome,
        u.email as usuario_email
    FROM movimentacoes m
    JOIN produtos p ON m.produto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($tipoFiltro) && in_array($tipoFiltro, ['entrada', 'saida'])) {
    $sql .= " AND m.tipo = :tipo";
    $params[':tipo'] = $tipoFiltro;
}

if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE :busca OR p.sku LIKE :busca OR m.motivo LIKE :busca OR m.documento LIKE :busca OR u.nome LIKE :busca)";
    $params[':busca'] = "%{$busca}%";
}

if (!empty($dataInicio)) {
    $sql .= " AND DATE(m.data_movimentacao) >= :data_inicio";
    $params[':data_inicio'] = $dataInicio;
}

if (!empty($dataFim)) {
    $sql .= " AND DATE(m.data_movimentacao) <= :data_fim";
    $params[':data_fim'] = $dataFim;
}

$sql .= " ORDER BY m.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimentacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    setToast('error', 'Erro ao consultar histórico de movimentações.');
    $movimentacoes = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase tracking-wider text-cyan-400">Auditoria & Rastreabilidade</span>
                <span class="text-slate-600">&bull;</span>
                <span class="text-xs text-slate-400"><?= count($movimentacoes) ?> registro(s)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Histórico de Movimentações
            </h1>
            <p class="text-sm text-slate-400">Linha do tempo de todas as entradas e saídas de materiais com saldo anterior e posterior.</p>
        </div>

        <div class="flex items-center gap-2">
            <?php if (canRegisterEntrada()): ?>
                <a href="movimentacao.php?tipo=entrada" class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all">
                    <i class="fa-solid fa-arrow-down"></i>
                    <span>Nova Entrada</span>
                </a>
            <?php endif; ?>
            <?php if (canRegisterSaida()): ?>
                <a href="movimentacao.php?tipo=saida" class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/20 transition-all">
                    <i class="fa-solid fa-arrow-up"></i>
                    <span>Nova Saída</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl mb-6">
        <form method="GET" action="historico.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <!-- Campo de Busca Geral -->
            <div class="relative lg:col-span-2">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
                       placeholder="Buscar por produto, SKU, NF ou operador..."
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
            </div>

            <!-- Tipo -->
            <div>
                <select name="tipo" onchange="this.form.submit()"
                        class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <option value="">Todos os Tipos</option>
                    <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Somente Entradas</option>
                    <option value="saida" <?= $tipoFiltro === 'saida' ? 'selected' : '' ?>>Somente Saídas</option>
                </select>
            </div>

            <!-- Data Início -->
            <div>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>"
                       class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors" title="Data Inicial">
            </div>

            <!-- Data Fim e Ações -->
            <div class="flex items-center gap-2">
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>"
                       class="flex-1 py-2.5 px-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors" title="Data Final">
                
                <button type="submit" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold shadow-md shadow-cyan-600/30 transition-all">
                    Filtrar
                </button>

                <?php if (!empty($busca) || !empty($tipoFiltro) || !empty($dataInicio) || !empty($dataFim)): ?>
                    <a href="historico.php" class="p-2.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl text-sm transition-all" title="Limpar Filtros">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <!-- Tabela Responsiva de Movimentações -->
    <?php if (empty($movimentacoes)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center shadow-lg">
            <div class="w-16 h-16 rounded-2xl bg-slate-800 flex items-center justify-center text-slate-500 text-2xl mx-auto mb-4">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h3 class="text-lg font-bold text-white">Nenhum registro encontrado</h3>
            <p class="text-sm text-slate-400 mt-1">Não foram encontradas movimentações para os filtros aplicados.</p>
        </div>
    <?php else: ?>

        <!-- Visualização Desktop (Tabela) -->
        <div class="hidden lg:block bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-950/70 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th scope="col" class="py-4 px-6">Data / Hora</th>
                            <th scope="col" class="py-4 px-6">Tipo</th>
                            <th scope="col" class="py-4 px-6">Produto / SKU</th>
                            <th scope="col" class="py-4 px-6 text-center">Quantidade</th>
                            <th scope="col" class="py-4 px-6 text-center">Saldo (Antes &rarr; Depois)</th>
                            <th scope="col" class="py-4 px-6">Motivo & Documento</th>
                            <th scope="col" class="py-4 px-6">Operador</th>
                            <th scope="col" class="py-4 px-6 text-center">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($movimentacoes as $m): ?>
                            <?php $isEntrada = $m['tipo'] === 'entrada'; ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-4 px-6 text-xs text-slate-300 font-mono whitespace-nowrap">
                                    <?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase <?= $isEntrada ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-blue-950 text-blue-300 border border-blue-800' ?>">
                                        <i class="fa-solid <?= $isEntrada ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                                        <?= $isEntrada ? 'Entrada' : 'Saída' ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-white"><?= htmlspecialchars($m['produto_nome']) ?></div>
                                    <div class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($m['produto_sku']) ?></div>
                                </td>
                                <td class="py-4 px-6 text-center font-extrabold whitespace-nowrap">
                                    <span class="<?= $isEntrada ? 'text-emerald-400' : 'text-blue-400' ?>">
                                        <?= $isEntrada ? '+' : '-' ?><?= number_format($m['quantidade'], 0, ',', '.') ?> <?= htmlspecialchars($m['produto_unidade']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center text-xs whitespace-nowrap">
                                    <span class="text-slate-400 font-mono"><?= $m['saldo_anterior'] ?></span>
                                    <span class="text-slate-600 mx-1">&rarr;</span>
                                    <span class="text-white font-bold font-mono"><?= $m['saldo_posterior'] ?></span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-slate-200 font-medium"><?= htmlspecialchars($m['motivo']) ?></div>
                                    <?php if (!empty($m['documento'])): ?>
                                        <div class="text-xs text-slate-400 font-mono flex items-center gap-1 mt-0.5">
                                            <i class="fa-solid fa-file-invoice text-slate-500"></i>
                                            <?= htmlspecialchars($m['documento']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-300 whitespace-nowrap">
                                    <div class="font-medium"><?= htmlspecialchars($m['usuario_nome']) ?></div>
                                    <div class="text-[10px] text-slate-500"><?= htmlspecialchars($m['usuario_email']) ?></div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <button type="button" 
                                            onclick="showDetalhesMovimento(<?= htmlspecialchars(json_encode($m)) ?>)"
                                            class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors text-xs"
                                            title="Ver Detalhes">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visualização Mobile / Tablet (Cards Mobile-First) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
            <?php foreach ($movimentacoes as $m): ?>
                <?php $isEntrada = $m['tipo'] === 'entrada'; ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase <?= $isEntrada ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-blue-950 text-blue-300 border border-blue-800' ?>">
                                <i class="fa-solid <?= $isEntrada ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                                <?= $isEntrada ? 'Entrada' : 'Saída' ?>
                            </span>
                            <span class="text-xs text-slate-400 font-mono">
                                <?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?>
                            </span>
                        </div>

                        <h3 class="text-sm font-bold text-white mt-1">
                            <?= htmlspecialchars($m['produto_nome']) ?>
                        </h3>
                        <p class="text-xs text-slate-400 font-mono">
                            SKU: <?= htmlspecialchars($m['produto_sku']) ?>
                        </p>

                        <div class="mt-3 p-3 bg-slate-950/70 rounded-xl border border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-500 block">Movimentado</span>
                                <span class="text-sm font-extrabold <?= $isEntrada ? 'text-emerald-400' : 'text-blue-400' ?>">
                                    <?= $isEntrada ? '+' : '-' ?><?= $m['quantidade'] ?> <?= htmlspecialchars($m['produto_unidade']) ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-500 block">Saldo Resultante</span>
                                <span class="text-sm font-bold text-white font-mono"><?= $m['saldo_posterior'] ?> <?= htmlspecialchars($m['produto_unidade']) ?></span>
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-slate-300">
                            <p class="font-medium"><?= htmlspecialchars($m['motivo']) ?></p>
                            <?php if (!empty($m['documento'])): ?>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5"><i class="fa-solid fa-file-invoice text-slate-500 mr-1"></i><?= htmlspecialchars($m['documento']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <span><i class="fa-regular fa-user mr-1 text-slate-500"></i><?= htmlspecialchars($m['usuario_nome']) ?></span>
                        <button type="button" 
                                onclick="showDetalhesMovimento(<?= htmlspecialchars(json_encode($m)) ?>)"
                                class="text-cyan-400 hover:text-cyan-300 font-semibold text-xs">
                            Ver Detalhes &rarr;
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</main>

<script>
function showDetalhesMovimento(m) {
    const isEntrada = m.tipo === 'entrada';
    const msg = `
Produto: ${m.produto_nome} (${m.produto_sku})
Tipo: ${isEntrada ? 'ENTRADA' : 'SAÍDA'}
Quantidade: ${m.quantidade} ${m.produto_unidade}
Saldo Anterior: ${m.saldo_anterior} -> Saldo Posterior: ${m.saldo_posterior}
Motivo: ${m.motivo}
Documento: ${m.documento || 'Não informado'}
Operador Responsável: ${m.usuario_nome} (${m.usuario_email})
Data e Hora: ${m.data_movimentacao}
Observações: ${m.observacoes || 'Nenhuma observação registrada.'}
    `.trim();

    showConfirmModal(
        `Detalhes da Movimentação #${m.id}`,
        msg,
        null,
        'alert',
        'Fechar'
    );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
