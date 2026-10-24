<?php
/**
 * NEXO WMS - Relatórios Gerenciais e Indicadores de Estoque (Restrito a Gestores)
 */
define('PAGE_TITLE', 'Relatórios & Indicadores');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireGestor();

$tipoFiltro   = trim($_GET['tipo'] ?? '');
$categoriaId  = trim($_GET['categoria'] ?? '');
$dataInicio   = trim($_GET['data_inicio'] ?? date('Y-m-01')); // Início do mês atual por padrão
$dataFim      = trim($_GET['data_fim'] ?? date('Y-m-d'));      // Hoje
$exportCsv    = ($_GET['export'] ?? '') === 'csv';

// Construção da Query de Movimentações
$sql = "
    SELECT 
        m.*, 
        p.nome as produto_nome, 
        p.sku as produto_sku, 
        p.unidade as produto_unidade,
        p.preco as produto_preco,
        c.nome as categoria_nome,
        u.nome as usuario_nome
    FROM movimentacoes m
    JOIN produtos p ON m.produto_id = p.id
    LEFT JOIN categorias c ON p.categoria_id = c.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($tipoFiltro) && in_array($tipoFiltro, ['entrada', 'saida'])) {
    $sql .= " AND m.tipo = :tipo";
    $params[':tipo'] = $tipoFiltro;
}

if (!empty($categoriaId)) {
    $sql .= " AND p.categoria_id = :cat";
    $params[':cat'] = $categoriaId;
}

if (!empty($dataInicio)) {
    $sql .= " AND DATE(m.data_movimentacao) >= :data_inicio";
    $params[':data_inicio'] = $dataInicio;
}

if (!empty($dataFim)) {
    $sql .= " AND DATE(m.data_movimentacao) <= :data_fim";
    $params[':data_fim'] = $dataFim;
}

$sql .= " ORDER BY m.data_movimentacao DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimentacoes = $stmt->fetchAll();

    // Exportação em formato CSV
    if ($exportCsv) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=nexo_wms_relatorio_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
        fputcsv($out, ['ID', 'Data/Hora', 'Tipo', 'SKU', 'Produto', 'Categoria', 'Quantidade', 'Unidade', 'Saldo Anterior', 'Saldo Posterior', 'Motivo', 'Documento', 'Operador'], ';');
        foreach ($movimentacoes as $row) {
            fputcsv($out, [
                $row['id'],
                $row['data_movimentacao'],
                strtoupper($row['tipo']),
                $row['produto_sku'],
                $row['produto_nome'],
                $row['categoria_nome'] ?? 'Geral',
                $row['quantidade'],
                $row['produto_unidade'],
                $row['saldo_anterior'],
                $row['saldo_posterior'],
                $row['motivo'],
                $row['documento'] ?? '',
                $row['usuario_nome']
            ], ';');
        }
        fclose($out);
        exit;
    }

    // Totais e Indicadores do Período
    $totalEntradasItens = 0;
    $totalSaidasItens = 0;
    $totalEntradasOps = 0;
    $totalSaidasOps = 0;

    foreach ($movimentacoes as $m) {
        if ($m['tipo'] === 'entrada') {
            $totalEntradasItens += (int)$m['quantidade'];
            $totalEntradasOps++;
        } else {
            $totalSaidasItens += (int)$m['quantidade'];
            $totalSaidasOps++;
        }
    }

    // Top 5 Produtos Mais Movimentados
    $stmtTop = $pdo->prepare("
        SELECT 
            p.nome, 
            p.sku, 
            p.unidade,
            SUM(m.quantidade) as total_volume,
            COUNT(m.id) as total_operacoes
        FROM movimentacoes m
        JOIN produtos p ON m.produto_id = p.id
        WHERE DATE(m.data_movimentacao) >= :d_inicio AND DATE(m.data_movimentacao) <= :d_fim
        GROUP BY p.id, p.nome, p.sku, p.unidade
        ORDER BY total_volume DESC
        LIMIT 5
    ");
    $stmtTop->execute([
        ':d_inicio' => $dataInicio ?: '2000-01-01',
        ':d_fim'    => $dataFim ?: '2099-12-31'
    ]);
    $topProdutos = $stmtTop->fetchAll();

    $categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();

} catch (PDOException $e) {
    setToast('error', 'Erro ao compilar relatórios: ' . $e->getMessage());
    $movimentacoes = [];
    $topProdutos = [];
    $categorias = [];
    $totalEntradasItens = 0;
    $totalSaidasItens = 0;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase tracking-wider text-purple-400">Inteligência Operacional</span>
                <span class="text-slate-600">&bull;</span>
                <span class="text-xs text-slate-400">Painel Executivo</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Relatórios & Indicadores de Estoque
            </h1>
            <p class="text-sm text-slate-400">Analise o giro de materiais, volume de expedição e balanço de cargas por período.</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Botão Imprimir / PDF -->
            <button type="button" onclick="window.print()" class="px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white border border-slate-700 transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Imprimir Relatório</span>
            </button>

            <!-- Exportar CSV -->
            <a href="relatorios.php?export=csv&tipo=<?= urlencode($tipoFiltro) ?>&categoria=<?= urlencode($categoriaId) ?>&data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>" 
               class="px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i>
                <span>Exportar CSV</span>
            </a>
        </div>
    </div>

    <!-- Filtros de Período e Tipo -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl mb-6">
        <form method="GET" action="relatorios.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>"
                       class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>"
                       class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipo de Operação</label>
                <select name="tipo"
                        class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Todas (Entrada & Saída)</option>
                    <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Somente Entradas</option>
                    <option value="saida" <?= $tipoFiltro === 'saida' ? 'selected' : '' ?>>Somente Saídas</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Categoria</label>
                <select name="categoria"
                        class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $categoriaId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-sm font-bold shadow-md shadow-purple-600/30 transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>Atualizar</span>
                </button>
                <a href="relatorios.php" class="p-2.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl text-sm transition-all" title="Limpar">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Indicadores Resumidos do Período -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total de Operações</span>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white"><?= count($movimentacoes) ?></span>
                <span class="text-xs text-slate-400">registros</span>
            </div>
            <p class="text-xs text-slate-400 mt-1"><?= $totalEntradasOps ?> entradas / <?= $totalSaidasOps ?> saídas</p>
        </div>

        <div class="bg-slate-900 border border-emerald-900/50 bg-emerald-950/10 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Volume Recebido (Entradas)</span>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-emerald-300"><?= number_format($totalEntradasItens, 0, ',', '.') ?></span>
                <span class="text-xs text-slate-400">unidades</span>
            </div>
            <p class="text-xs text-emerald-400/80 mt-1"><?= $totalEntradasOps ?> cargas registradas</p>
        </div>

        <div class="bg-slate-900 border border-blue-900/50 bg-blue-950/10 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-bold uppercase tracking-wider text-blue-400">Volume Expedido (Saídas)</span>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-blue-300"><?= number_format($totalSaidasItens, 0, ',', '.') ?></span>
                <span class="text-xs text-slate-400">unidades</span>
            </div>
            <p class="text-xs text-blue-400/80 mt-1"><?= $totalSaidasOps ?> ordens baixadas</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Balanço Líquido do Período</span>
            <?php $balanco = $totalEntradasItens - $totalSaidasItens; ?>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold <?= $balanco >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                    <?= ($balanco >= 0 ? '+' : '') . number_format($balanco, 0, ',', '.') ?>
                </span>
                <span class="text-xs text-slate-400">itens</span>
            </div>
            <p class="text-xs text-slate-400 mt-1"><?= $balanco >= 0 ? 'Acúmulo de estoque' : 'Consumo maior que reposição' ?></p>
        </div>

    </div>

    <!-- Grade: Top 5 Produtos Mais Movimentados & Distribuição -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <div class="lg:col-span-1 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center gap-2.5 pb-4 border-b border-slate-800 mb-4">
                <div class="w-8 h-8 rounded-lg bg-purple-950 text-purple-400 border border-purple-800 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Top 5 Mais Movimentados</h3>
                    <p class="text-[11px] text-slate-400">Materiais com maior volume de fluxo</p>
                </div>
            </div>

            <div class="space-y-3">
                <?php if (empty($topProdutos)): ?>
                    <p class="text-xs text-slate-500 py-6 text-center">Nenhuma movimentação no período selecionado.</p>
                <?php else: ?>
                    <?php foreach ($topProdutos as $idx => $tp): ?>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0 pr-2">
                                <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-300 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                    <?= $idx + 1 ?>
                                </span>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate"><?= htmlspecialchars($tp['nome']) ?></h4>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($tp['sku']) ?></span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-xs font-black text-cyan-400"><?= number_format($tp['total_volume'], 0, ',', '.') ?> <?= htmlspecialchars($tp['unidade']) ?></span>
                                <div class="text-[10px] text-slate-500"><?= $tp['total_operacoes'] ?> movs</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabela Sintética do Relatório (2 cols) -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-cyan-950 text-cyan-400 border border-cyan-800 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Extrato de Operações Filtradas</h3>
                        <p class="text-[11px] text-slate-400">Totalizando <?= count($movimentacoes) ?> lançamentos de armazém</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-left text-xs whitespace-nowrap">
                    <thead class="border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="pb-3 pr-3">Data</th>
                            <th class="pb-3 px-3">Tipo</th>
                            <th class="pb-3 px-3">Produto</th>
                            <th class="pb-3 px-3 text-center">Volume</th>
                            <th class="pb-3 px-3">Saldo</th>
                            <th class="pb-3 px-3">Documento / Motivo</th>
                            <th class="pb-3 pl-3">Responsável</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach (array_slice($movimentacoes, 0, 15) as $m): ?>
                            <?php $isEntrada = $m['tipo'] === 'entrada'; ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-2.5 pr-3 font-mono text-slate-400"><?= date('d/m H:i', strtotime($m['data_movimentacao'])) ?></td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $isEntrada ? 'bg-emerald-950 text-emerald-300' : 'bg-blue-950 text-blue-300' ?>">
                                        <?= $isEntrada ? 'Entrada' : 'Saída' ?>
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-white"><?= htmlspecialchars($m['produto_nome']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($m['produto_sku']) ?></div>
                                </td>
                                <td class="py-2.5 px-3 text-center font-bold <?= $isEntrada ? 'text-emerald-400' : 'text-blue-400' ?>">
                                    <?= $isEntrada ? '+' : '-' ?><?= $m['quantidade'] ?>
                                </td>
                                <td class="py-2.5 px-3 font-mono text-slate-300">
                                    <?= $m['saldo_anterior'] ?> &rarr; <strong class="text-white"><?= $m['saldo_posterior'] ?></strong>
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="text-slate-300"><?= htmlspecialchars($m['motivo']) ?></div>
                                    <?php if (!empty($m['documento'])): ?>
                                        <div class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($m['documento']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 pl-3 text-slate-400">
                                    <?= htmlspecialchars($m['usuario_nome']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($movimentacoes) > 15): ?>
                <p class="text-[11px] text-slate-500 text-center mt-3 pt-2 border-t border-slate-800">
                    Mostrando os 15 lançamentos mais recentes de um total de <?= count($movimentacoes) ?>. Para ver a lista completa, utilize o botão "Exportar CSV" ou acesse o Histórico Completo.
                </p>
            <?php endif; ?>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
