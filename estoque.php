<?php
/**
 * NEXO WMS - Consulta de Estoque e Inventário em Tempo Real
 */
define('PAGE_TITLE', 'Consulta de Estoque');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$busca = trim($_GET['busca'] ?? '');
$filtro = trim($_GET['filtro'] ?? ''); // 'alerta', 'zerado', 'todos'
$categoriaId = trim($_GET['categoria'] ?? '');

$sql = "
    SELECT 
        p.*, 
        c.nome as categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE :busca OR p.sku LIKE :busca OR p.localizacao LIKE :busca)";
    $params[':busca'] = "%{$busca}%";
}

if (!empty($categoriaId)) {
    $sql .= " AND p.categoria_id = :cat";
    $params[':cat'] = $categoriaId;
}

if ($filtro === 'alerta') {
    $sql .= " AND p.quantidade <= p.estoque_minimo AND p.quantidade > 0";
} elseif ($filtro === 'zerado') {
    $sql .= " AND p.quantidade = 0";
} elseif ($filtro === 'baixo_total') {
    $sql .= " AND p.quantidade <= p.estoque_minimo";
}

$sql .= " ORDER BY p.quantidade ASC, p.nome ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();

    $categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
} catch (PDOException $e) {
    setToast('error', 'Erro ao carregar o estoque.');
    $produtos = [];
    $categorias = [];
}

$canEntrada = canRegisterEntrada();
$canSaida = canRegisterSaida();
$isGest = isGestor();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <!-- Cabeçalho da Página -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase tracking-wider text-cyan-400">Armazém Central</span>
                <span class="text-slate-600">&bull;</span>
                <span class="text-xs text-slate-400"><?= count($produtos) ?> item(ns) encontrado(s)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Consulta de Estoque
            </h1>
            <p class="text-sm text-slate-400">Verifique a disponibilidade, saldo e endereço físico dos materiais nas prateleiras.</p>
        </div>

        <div class="flex items-center gap-2">
            <?php if ($canEntrada): ?>
                <a href="movimentacao.php?tipo=entrada" class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all">
                    <i class="fa-solid fa-arrow-down"></i>
                    <span>Nova Entrada</span>
                </a>
            <?php endif; ?>
            <?php if ($canSaida): ?>
                <a href="movimentacao.php?tipo=saida" class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/20 transition-all">
                    <i class="fa-solid fa-arrow-up"></i>
                    <span>Nova Saída</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Filtros e Busca Responsiva -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl mb-6">
        <form method="GET" action="estoque.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Campo de Busca Textual -->
            <div class="relative lg:col-span-2">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
                       placeholder="Buscar por SKU, nome do produto ou localização..."
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
            </div>

            <!-- Filtro de Categoria -->
            <div>
                <select name="categoria" onchange="this.form.submit()"
                        class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoriaId == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro de Nível de Estoque -->
            <div class="flex items-center gap-2">
                <select name="filtro" onchange="this.form.submit()"
                        class="flex-1 py-2.5 px-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <option value="">Todos os Níveis</option>
                    <option value="baixo_total" <?= $filtro === 'baixo_total' ? 'selected' : '' ?>>Abaixo do Mínimo</option>
                    <option value="alerta" <?= $filtro === 'alerta' ? 'selected' : '' ?>>Estoque Baixo</option>
                    <option value="zerado" <?= $filtro === 'zerado' ? 'selected' : '' ?>>Zerado (Crítico)</option>
                </select>

                <button type="submit" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold shadow-md shadow-cyan-600/30 transition-all">
                    Filtrar
                </button>

                <?php if (!empty($busca) || !empty($filtro) || !empty($categoriaId)): ?>
                    <a href="estoque.php" class="p-2.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl text-sm transition-all" title="Limpar Filtros">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <!-- Lista de Estoque (Mobile: Cards / Desktop: Tabela) -->
    <?php if (empty($produtos)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center shadow-lg">
            <div class="w-16 h-16 rounded-2xl bg-slate-800 flex items-center justify-center text-slate-500 text-2xl mx-auto mb-4">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <h3 class="text-lg font-bold text-white">Nenhum produto encontrado</h3>
            <p class="text-sm text-slate-400 mt-1">Tente ajustar seus termos de pesquisa ou remover os filtros aplicados.</p>
            <div class="mt-6">
                <a href="estoque.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-cyan-400 bg-cyan-950/50 border border-cyan-800 hover:bg-cyan-900/50">
                    <i class="fa-solid fa-rotate-left"></i> Limpar Filtros
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Visualização Desktop (Tabela Completa) -->
        <div class="hidden md:block bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-950/70 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th scope="col" class="py-4 px-6">Produto / SKU</th>
                            <th scope="col" class="py-4 px-6">Categoria</th>
                            <th scope="col" class="py-4 px-6">Localização no Armazém</th>
                            <th scope="col" class="py-4 px-6 text-center">Nível / Saldo</th>
                            <th scope="col" class="py-4 px-6 text-center">Status</th>
                            <th scope="col" class="py-4 px-6 text-right">Ações Rápidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($produtos as $p): ?>
                            <?php
                                $qtd = (int)$p['quantidade'];
                                $min = (int)$p['estoque_minimo'];
                                $statusBadge = 'bg-emerald-950 text-emerald-300 border-emerald-800';
                                $statusLabel = 'Normal';
                                $textColor = 'text-white';

                                if ($qtd == 0) {
                                    $statusBadge = 'bg-rose-950 text-rose-300 border-rose-800';
                                    $statusLabel = 'Zerado';
                                    $textColor = 'text-rose-400';
                                } elseif ($qtd <= $min) {
                                    $statusBadge = 'bg-amber-950 text-amber-300 border-amber-800';
                                    $statusLabel = 'Baixo';
                                    $textColor = 'text-amber-400';
                                }
                            ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-white"><?= htmlspecialchars($p['nome']) ?></div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5"><?= htmlspecialchars($p['sku']) ?></div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 text-slate-300">
                                        <?= htmlspecialchars($p['categoria_nome'] ?? 'Sem Categoria') ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2 text-slate-300 font-medium text-xs">
                                        <i class="fa-solid fa-location-dot text-cyan-400"></i>
                                        <span><?= htmlspecialchars($p['localizacao']) ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-base font-extrabold <?= $textColor ?>">
                                        <?= number_format($qtd, 0, ',', '.') ?>
                                    </span>
                                    <span class="text-xs text-slate-400 ml-0.5"><?= htmlspecialchars($p['unidade']) ?></span>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Mínimo: <?= $min ?></div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border <?= $statusBadge ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $qtd == 0 ? 'bg-rose-400' : ($qtd <= $min ? 'bg-amber-400' : 'bg-emerald-400') ?>"></span>
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($canEntrada): ?>
                                            <a href="movimentacao.php?tipo=entrada&produto_id=<?= $p['id'] ?>" class="p-2 rounded-lg bg-emerald-950 text-emerald-300 hover:bg-emerald-900 border border-emerald-800 text-xs transition-colors" title="Registrar Entrada">
                                                <i class="fa-solid fa-arrow-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canSaida): ?>
                                            <a href="movimentacao.php?tipo=saida&produto_id=<?= $p['id'] ?>" class="p-2 rounded-lg bg-blue-950 text-blue-300 hover:bg-blue-900 border border-blue-800 text-xs transition-colors" title="Registrar Saída">
                                                <i class="fa-solid fa-arrow-up"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isGest): ?>
                                            <a href="produto_form.php?id=<?= $p['id'] ?>" class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700 text-xs transition-colors" title="Editar Produto">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visualização Mobile (Cards Responsivos Mobile-First) -->
        <div class="grid grid-cols-1 gap-4 md:hidden">
            <?php foreach ($produtos as $p): ?>
                <?php
                    $qtd = (int)$p['quantidade'];
                    $min = (int)$p['estoque_minimo'];
                    $statusBadge = 'bg-emerald-950 text-emerald-300 border-emerald-800';
                    $statusLabel = 'Normal';
                    $textColor = 'text-emerald-400';

                    if ($qtd == 0) {
                        $statusBadge = 'bg-rose-950 text-rose-300 border-rose-800';
                        $statusLabel = 'Zerado';
                        $textColor = 'text-rose-400';
                    } elseif ($qtd <= $min) {
                        $statusBadge = 'bg-amber-950 text-amber-300 border-amber-800';
                        $statusLabel = 'Estoque Baixo';
                        $textColor = 'text-amber-400';
                    }
                ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                                <?= htmlspecialchars($p['sku']) ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold border <?= $statusBadge ?>">
                                <?= $statusLabel ?>
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-white">
                            <?= htmlspecialchars($p['nome']) ?>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Categoria: <span class="text-slate-300 font-medium"><?= htmlspecialchars($p['categoria_nome'] ?? 'Geral') ?></span>
                        </p>

                        <div class="mt-3 p-3 bg-slate-950/70 rounded-xl border border-slate-800/80 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-bold block">Localização</span>
                                <span class="text-xs font-semibold text-cyan-400 flex items-center gap-1 mt-0.5">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?= htmlspecialchars($p['localizacao']) ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-bold block">Saldo Atual</span>
                                <span class="text-lg font-black <?= $textColor ?>">
                                    <?= $qtd ?> <span class="text-xs text-slate-400"><?= htmlspecialchars($p['unidade']) ?></span>
                                </span>
                                <div class="text-[10px] text-slate-500">Mín: <?= $min ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações Mobile -->
                    <div class="mt-4 pt-3 border-t border-slate-800 flex items-center gap-2">
                        <?php if ($canEntrada): ?>
                            <a href="movimentacao.php?tipo=entrada&produto_id=<?= $p['id'] ?>" class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md transition-colors">
                                <i class="fa-solid fa-arrow-down"></i> Entrada
                            </a>
                        <?php endif; ?>
                        <?php if ($canSaida): ?>
                            <a href="movimentacao.php?tipo=saida&produto_id=<?= $p['id'] ?>" class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md transition-colors">
                                <i class="fa-solid fa-arrow-up"></i> Saída
                            </a>
                        <?php endif; ?>
                        <?php if ($isGest): ?>
                            <a href="produto_form.php?id=<?= $p['id'] ?>" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs transition-colors" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
