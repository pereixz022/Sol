<?php
/**
 * NEXO WMS - Registro de Movimentações de Mercadorias (Entradas e Saídas)
 */
define('PAGE_TITLE', 'Registrar Movimentação');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$tipo = strtolower(trim($_GET['tipo'] ?? 'entrada'));
if ($tipo !== 'saida') {
    $tipo = 'entrada';
}

$produtoIdPreSelecionado = (int)($_GET['produto_id'] ?? 0);

// Validação de Permissões Estritas
if ($tipo === 'entrada' && !canRegisterEntrada()) {
    setToast('error', 'Seu perfil de usuário não possui permissão para registrar ENTRADAS de mercadorias.');
    header('Location: dashboard.php');
    exit;
}

if ($tipo === 'saida' && !canRegisterSaida()) {
    setToast('error', 'Seu perfil de usuário não possui permissão para registrar SAÍDAS de mercadorias.');
    header('Location: dashboard.php');
    exit;
}

// Carregar todos os produtos ativos
try {
    $stmtProds = $pdo->query("
        SELECT id, sku, nome, quantidade, unidade, localizacao, estoque_minimo
        FROM produtos
        WHERE status = 'ativo'
        ORDER BY nome ASC
    ");
    $produtos = $stmtProds->fetchAll();
} catch (PDOException $e) {
    setToast('error', 'Erro ao carregar lista de materiais.');
    $produtos = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <div class="max-w-3xl mx-auto mb-6">
        <div class="flex items-center gap-2 mb-2">
            <a href="dashboard.php" class="text-xs text-slate-400 hover:text-cyan-400 transition-colors flex items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i> Painel Geral
            </a>
            <span class="text-slate-600">&bull;</span>
            <span class="text-xs font-mono uppercase tracking-wider <?= $tipo === 'entrada' ? 'text-emerald-400' : 'text-blue-400' ?>">
                Operação de Estoque
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            <?= $tipo === 'entrada' ? 'Registrar Entrada de Mercadoria' : 'Registrar Saída / Expedição de Mercadoria' ?>
        </h1>
        <p class="text-sm text-slate-400">
            <?= $tipo === 'entrada' ? 'Lançamento de cargas recebidas de fornecedores, transferências ou retornos.' : 'Separação e baixa de estoque para envio à produção, clientes ou manutenção.' ?>
        </p>
    </div>

    <!-- Seletor de Tipo (Abas com permissões visíveis) -->
    <div class="max-w-3xl mx-auto mb-6 flex rounded-xl bg-slate-900 border border-slate-800 p-1.5">
        <?php if (canRegisterEntrada()): ?>
            <a href="movimentacao.php?tipo=entrada<?= $produtoIdPreSelecionado ? "&produto_id={$produtoIdPreSelecionado}" : '' ?>"
               class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all <?= $tipo === 'entrada' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-400 hover:text-white' ?>">
                <i class="fa-solid fa-arrow-down"></i>
                <span>Operação de Entrada</span>
            </a>
        <?php else: ?>
            <div class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-bold text-slate-600 cursor-not-allowed" title="Sem permissão">
                <i class="fa-solid fa-lock text-xs"></i>
                <span>Entrada (Bloqueada)</span>
            </div>
        <?php endif; ?>

        <?php if (canRegisterSaida()): ?>
            <a href="movimentacao.php?tipo=saida<?= $produtoIdPreSelecionado ? "&produto_id={$produtoIdPreSelecionado}" : '' ?>"
               class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all <?= $tipo === 'saida' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-400 hover:text-white' ?>">
                <i class="fa-solid fa-arrow-up"></i>
                <span>Operação de Saída</span>
            </a>
        <?php else: ?>
            <div class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-bold text-slate-600 cursor-not-allowed" title="Sem permissão">
                <i class="fa-solid fa-lock text-xs"></i>
                <span>Saída (Bloqueada)</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card do Formulário -->
    <div class="max-w-3xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl">
        <form action="actions/movimentacao_action.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="tipo" value="<?= $tipo ?>">

            <!-- Seleção do Produto -->
            <div>
                <label for="produto_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                    Selecionar Produto do Armazém <span class="text-rose-400">*</span>
                </label>
                <select id="produto_id" name="produto_id" required onchange="atualizarInfoProduto()"
                        class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                    <option value="">-- Escolha um material cadastrado --</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= $p['id'] ?>" 
                                data-saldo="<?= $p['quantidade'] ?>"
                                data-unidade="<?= htmlspecialchars($p['unidade']) ?>"
                                data-local="<?= htmlspecialchars($p['localizacao']) ?>"
                                data-min="<?= $p['estoque_minimo'] ?>"
                                <?= $produtoIdPreSelecionado == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['sku']) ?> - <?= htmlspecialchars($p['nome']) ?> (Saldo atual: <?= $p['quantidade'] ?> <?= $p['unidade'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Painel Informativo Dinâmico do Produto Selecionado -->
            <div id="painel-produto-info" class="hidden p-4 rounded-xl bg-slate-950/80 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Endereço no Armazém</span>
                    <span id="info-local" class="text-cyan-400 font-semibold block mt-0.5">-</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Saldo Atual em Estoque</span>
                    <span id="info-saldo" class="text-white font-bold block mt-0.5 text-sm">-</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Estoque Mínimo</span>
                    <span id="info-min" class="text-slate-300 font-medium block mt-0.5">-</span>
                </div>
            </div>

            <!-- Quantidade e Motivo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="quantidade" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Quantidade a Movimentar <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <input type="number" id="quantidade" name="quantidade" min="1" required
                               placeholder="Ex: 10"
                               class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                        <span id="label-unidade-input" class="absolute inset-y-0 right-0 pr-4 flex items-center text-xs font-bold text-slate-500 pointer-events-none">
                            UN
                        </span>
                    </div>
                    <?php if ($tipo === 'saida'): ?>
                        <p class="text-[11px] text-amber-400 mt-1">A quantidade de saída não pode ser maior que o saldo atual.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="motivo" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Motivo / Justificativa da Movimentação <span class="text-rose-400">*</span>
                    </label>
                    <select id="motivo" name="motivo" required
                            class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                        <?php if ($tipo === 'entrada'): ?>
                            <option value="Recebimento de Fornecedor">Recebimento de Fornecedor (Compra)</option>
                            <option value="Devolução de Cliente">Devolução de Cliente</option>
                            <option value="Retorno de Manutenção">Retorno de Manutenção</option>
                            <option value="Transferência entre Galpões">Transferência entre Galpões</option>
                            <option value="Ajuste de Inventário / Sobra">Ajuste de Inventário / Sobra</option>
                            <option value="Outro Motivo">Outro Motivo</option>
                        <?php else: ?>
                            <option value="Expedição de Pedido">Expedição de Pedido de Venda</option>
                            <option value="Envio para Linha de Produção">Envio para Linha de Produção</option>
                            <option value="Consumo Interno / EPI">Consumo Interno / Distribuição de EPI</option>
                            <option value="Transferência de Armazém">Transferência de Armazém</option>
                            <option value="Descarte / Avaria / Vencimento">Descarte / Avaria / Vencimento</option>
                            <option value="Ajuste de Inventário / Quebra">Ajuste de Inventário / Quebra</option>
                            <option value="Outro Motivo">Outro Motivo</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Documento Fiscal / Referência -->
            <div>
                <label for="documento" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                    Número de Documento / NF / Ordem de Carga
                </label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <input type="text" id="documento" name="documento"
                           placeholder="Ex: NF-0049281, REQ-2026-088 ou OS-12"
                           class="w-full pl-10 pr-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors">
                </div>
            </div>

            <!-- Observações Adicionais -->
            <div>
                <label for="observacoes" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                    Observações Operacionais (Opcional)
                </label>
                <textarea id="observacoes" name="observacoes" rows="3"
                          placeholder="Detalhes adicionais sobre o lote, conferência física ou transportadora..."
                          class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors"></textarea>
            </div>

            <!-- Botões de Envio -->
            <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="dashboard.php" class="w-full sm:w-auto px-5 py-3 rounded-xl text-center text-sm font-semibold text-slate-400 bg-slate-800 hover:bg-slate-700 hover:text-white transition-colors">
                    Cancelar
                </a>
                
                <button type="submit"
                        class="w-full sm:w-auto px-6 py-3 rounded-xl text-center text-sm font-bold text-white shadow-lg transition-all flex items-center justify-center gap-2 <?= $tipo === 'entrada' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30' : 'bg-blue-600 hover:bg-blue-500 shadow-blue-600/30' ?>">
                    <i class="fa-solid <?= $tipo === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                    <span>Confirmar <?= $tipo === 'entrada' ? 'Entrada no Estoque' : 'Baixa e Saída' ?></span>
                </button>
            </div>

        </form>
    </div>

</main>

<script>
function atualizarInfoProduto() {
    const select = document.getElementById('produto_id');
    const painel = document.getElementById('painel-produto-info');
    const opt = select.options[select.selectedIndex];
    const lblUnidade = document.getElementById('label-unidade-input');

    if (!opt || !opt.value) {
        painel.classList.add('hidden');
        if (lblUnidade) lblUnidade.textContent = 'UN';
        return;
    }

    const saldo = opt.getAttribute('data-saldo');
    const unidade = opt.getAttribute('data-unidade');
    const local = opt.getAttribute('data-local');
    const min = opt.getAttribute('data-min');

    document.getElementById('info-local').textContent = local;
    document.getElementById('info-saldo').textContent = `${saldo} ${unidade}`;
    document.getElementById('info-min').textContent = `${min} ${unidade}`;
    if (lblUnidade) lblUnidade.textContent = unidade;

    painel.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    atualizarInfoProduto();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
