<?php
/**
 * NEXO WMS - Cadastro e Edição de Produtos (Restrito ao Gestor)
 */
define('PAGE_TITLE', 'Formulário de Produto');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireGestor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdicao = $id > 0;

$produto = [
    'id'             => 0,
    'sku'            => '',
    'nome'           => '',
    'categoria_id'   => '',
    'localizacao'    => '',
    'unidade'        => 'UN',
    'preco'          => '0.00',
    'quantidade'     => '0',
    'estoque_minimo' => '10',
    'status'         => 'ativo'
];

if ($isEdicao) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $prodFound = $stmt->fetch();
        if ($prodFound) {
            $produto = $prodFound;
        } else {
            setToast('error', 'Produto não encontrado.');
            header('Location: produtos.php');
            exit;
        }
    } catch (PDOException $e) {
        setToast('error', 'Erro ao consultar produto.');
        header('Location: produtos.php');
        exit;
    }
}

// Carregar categorias
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <!-- Cabeçalho -->
    <div class="max-w-4xl mx-auto mb-6">
        <div class="flex items-center gap-2 mb-2">
            <a href="produtos.php" class="text-xs text-slate-400 hover:text-cyan-400 transition-colors flex items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Catálogo
            </a>
            <span class="text-slate-600">&bull;</span>
            <span class="text-xs text-purple-400 font-bold uppercase">Módulo de Gestão</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            <?= $isEdicao ? 'Editar Produto: ' . htmlspecialchars($produto['nome']) : 'Cadastrar Novo Produto' ?>
        </h1>
        <p class="text-sm text-slate-400">Preencha as informações cadastrais e parâmetros de localização no armazém.</p>
    </div>

    <!-- Formulário Principal -->
    <div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl">
        <form action="actions/produto_action.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="acao" value="<?= $isEdicao ? 'salvar_edicao' : 'novo' ?>">
            <input type="hidden" name="id" value="<?= $produto['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- SKU (Código Único de Identificação) -->
                <div>
                    <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Código SKU / Código de Barras <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="sku" name="sku" required
                           value="<?= htmlspecialchars($produto['sku']) ?>"
                           placeholder="Ex: EPI-CAP-001"
                           class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <p class="text-[11px] text-slate-500 mt-1">Identificador único do material no sistema.</p>
                </div>

                <!-- Nome do Produto -->
                <div>
                    <label for="nome" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Nome do Produto <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="nome" name="nome" required
                           value="<?= htmlspecialchars($produto['nome']) ?>"
                           placeholder="Ex: Capacete de Segurança com Aba Frontal"
                           class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                </div>

                <!-- Categoria -->
                <div>
                    <label for="categoria_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Categoria de Material
                    </label>
                    <select id="categoria_id" name="categoria_id"
                            class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                        <option value="">Selecione uma categoria...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $produto['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Localização Física no Armazém -->
                <div>
                    <label for="localizacao" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Localização Física (Endereçamento) <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="localizacao" name="localizacao" required
                           value="<?= htmlspecialchars($produto['localizacao']) ?>"
                           placeholder="Ex: Corredor A - Prateleira 2 - Nível 3"
                           class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                </div>

                <!-- Unidade de Medida -->
                <div>
                    <label for="unidade" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Unidade de Medida
                    </label>
                    <select id="unidade" name="unidade"
                            class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                        <?php 
                        $unidades = ['UN' => 'Unidade (UN)', 'CX' => 'Caixa (CX)', 'PAR' => 'Par (PAR)', 'KG' => 'Quilograma (KG)', 'LT' => 'Litro (LT)', 'RL' => 'Rolo (RL)', 'MT' => 'Metro (MT)', 'PC' => 'Peça (PC)'];
                        foreach ($unidades as $sigla => $lbl): ?>
                            <option value="<?= $sigla ?>" <?= $produto['unidade'] === $sigla ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Preço Unitário / Custo -->
                <div>
                    <label for="preco" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Preço Unitário Estimado (R$)
                    </label>
                    <input type="number" step="0.01" min="0" id="preco" name="preco"
                           value="<?= htmlspecialchars($produto['preco']) ?>"
                           class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                </div>

                <!-- Quantidade em Estoque -->
                <div>
                    <label for="quantidade" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        <?= $isEdicao ? 'Saldo Atual em Estoque' : 'Quantidade Inicial de Entrada' ?> <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" min="0" id="quantidade" name="quantidade" required
                           value="<?= (int)$produto['quantidade'] ?>"
                           class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <p class="text-[11px] text-slate-500 mt-1">
                        <?= $isEdicao ? 'Ajustes rotineiros de entrada e saída devem ser feitos via Movimentações.' : 'Será registrado um lançamento de carga inicial no histórico.' ?>
                    </p>
                </div>

                <!-- Estoque Mínimo (Ponto de Reposição) -->
                <div>
                    <label for="estoque_minimo" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Estoque Mínimo (Alerta de Reposição) <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" min="0" id="estoque_minimo" name="estoque_minimo" required
                           value="<?= (int)$produto['estoque_minimo'] ?>"
                           class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <p class="text-[11px] text-slate-500 mt-1">Dispara aviso visual no dashboard quando o saldo atingir este patamar.</p>
                </div>

                <!-- Status do Produto -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                        Situação do Material
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="ativo" <?= $produto['status'] === 'ativo' ? 'checked' : '' ?> class="text-cyan-600 focus:ring-cyan-500">
                            <span class="text-sm font-medium text-emerald-400"><i class="fa-solid fa-circle text-xs mr-1"></i> Ativo (Disponível para movimentação)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="inativo" <?= $produto['status'] === 'inativo' ? 'checked' : '' ?> class="text-rose-600 focus:ring-rose-500">
                            <span class="text-sm font-medium text-rose-400"><i class="fa-solid fa-circle text-xs mr-1"></i> Inativo (Bloqueado)</span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Botões de Ação -->
            <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="produtos.php" class="w-full sm:w-auto px-5 py-3 rounded-xl text-center text-sm font-semibold text-slate-400 bg-slate-800 hover:bg-slate-700 hover:text-white transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="w-full sm:w-auto px-6 py-3 rounded-xl text-center text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-600/30 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span><?= $isEdicao ? 'Salvar Alterações' : 'Concluir Cadastro' ?></span>
                </button>
            </div>
        </form>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
