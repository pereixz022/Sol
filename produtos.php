<?php
/**
 * NEXO WMS - Catálogo Geral de Produtos
 */
define('PAGE_TITLE', 'Catálogo de Produtos');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$busca = trim($_GET['busca'] ?? '');
$isGest = isGestor();

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

$sql .= " ORDER BY p.nome ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    setToast('error', 'Erro ao carregar catálogo de produtos.');
    $produtos = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <!-- Cabeçalho da Página -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase tracking-wider text-cyan-400">Catálogo de Armazenamento</span>
                <span class="text-slate-600">&bull;</span>
                <span class="text-xs text-slate-400"><?= count($produtos) ?> item(ns)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Produtos do Armazém
            </h1>
            <p class="text-sm text-slate-400">Relação completa de códigos SKU, descrições e parâmetros de reposição.</p>
        </div>

        <?php if ($isGest): ?>
            <div>
                <a href="produto_form.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-600/25 transition-all">
                    <i class="fa-solid fa-plus"></i>
                    <span>Cadastrar Novo Produto</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Barra de Pesquisa -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl mb-6">
        <form method="GET" action="produtos.php" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
                       placeholder="Buscar por nome, SKU ou localização..."
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold shadow-md shadow-cyan-600/30 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-search"></i>
                    <span>Pesquisar</span>
                </button>
                <?php if (!empty($busca)): ?>
                    <a href="produtos.php" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm transition-all">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Grid / Cards de Produtos -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($produtos as $p): ?>
            <?php
                $qtd = (int)$p['quantidade'];
                $min = (int)$p['estoque_minimo'];
            ?>
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all group">
                <div>
                    <!-- Topo do Card -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-cyan-950 text-cyan-300 border border-cyan-800/80">
                            <?= htmlspecialchars($p['sku']) ?>
                        </span>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $p['status'] === 'ativo' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' ?>">
                            <?= ucfirst($p['status']) ?>
                        </span>
                    </div>

                    <h2 class="text-base font-bold text-white group-hover:text-cyan-300 transition-colors">
                        <?= htmlspecialchars($p['nome']) ?>
                    </h2>

                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                        <i class="fa-solid fa-tag text-slate-500"></i>
                        <span><?= htmlspecialchars($p['categoria_nome'] ?? 'Sem Categoria') ?></span>
                    </div>

                    <!-- Especificações de Armazém -->
                    <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-slate-800/80 text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-500 block">Posição / Local</span>
                            <span class="text-slate-200 font-medium truncate block mt-0.5" title="<?= htmlspecialchars($p['localizacao']) ?>">
                                <?= htmlspecialchars($p['localizacao']) ?>
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-500 block">Estoque Atual</span>
                            <span class="text-white font-extrabold block mt-0.5 <?= $qtd <= $min ? 'text-amber-400' : 'text-emerald-400' ?>">
                                <?= $qtd ?> <?= htmlspecialchars($p['unidade']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-slate-400 px-1">
                        <span>Estoque Mínimo: <strong class="text-slate-300"><?= $min ?> <?= htmlspecialchars($p['unidade']) ?></strong></span>
                        <?php if ($isGest): ?>
                            <span>Valor Un: <strong class="text-slate-300">R$ <?= number_format($p['preco'], 2, ',', '.') ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações do Gestor / Operação -->
                <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <a href="estoque.php?busca=<?= urlencode($p['sku']) ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors">
                            <i class="fa-solid fa-eye mr-1"></i> Ver Saldo
                        </a>
                    </div>

                    <?php if ($isGest): ?>
                        <div class="flex items-center gap-1.5">
                            <a href="produto_form.php?id=<?= $p['id'] ?>" class="p-2 rounded-lg bg-purple-950/60 hover:bg-purple-900 border border-purple-800 text-purple-300 text-xs transition-colors" title="Editar Produto">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            
                            <!-- Botão de Excluir / Desativar com Modal Tailwind Customizado -->
                            <button type="button"
                                    data-confirm="Tem certeza que deseja excluir ou desativar o produto '<?= htmlspecialchars(addslashes($p['nome'])) ?>' (SKU: <?= htmlspecialchars($p['sku']) ?>)?"
                                    data-title="Excluir Produto"
                                    data-type="danger"
                                    data-btn-text="Sim, Excluir"
                                    href="actions/produto_action.php?acao=excluir&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                    class="p-2 rounded-lg bg-rose-950/60 hover:bg-rose-900 border border-rose-800 text-rose-300 text-xs transition-colors" title="Excluir Produto">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
