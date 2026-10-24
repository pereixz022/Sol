<?php
/**
 * NEXO WMS - Cadastro e Edição de Usuário e Permissões (Restrito a Gestores)
 */
define('PAGE_TITLE', 'Formulário de Usuário');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireGestor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdicao = $id > 0;

$usuario = [
    'id'           => 0,
    'nome'         => '',
    'email'        => '',
    'perfil'       => 'funcionario',
    'pode_entrada' => 1,
    'pode_saida'   => 1,
    'status'       => 'ativo'
];

if ($isEdicao) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome, email, perfil, pode_entrada, pode_saida, status FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $userFound = $stmt->fetch();
        if ($userFound) {
            $usuario = $userFound;
        } else {
            setToast('error', 'Usuário não localizado no sistema.');
            header('Location: usuarios.php');
            exit;
        }
    } catch (PDOException $e) {
        setToast('error', 'Erro ao consultar dados do usuário.');
        header('Location: usuarios.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <div class="max-w-2xl mx-auto mb-6">
        <div class="flex items-center gap-2 mb-2">
            <a href="usuarios.php" class="text-xs text-slate-400 hover:text-cyan-400 transition-colors flex items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Lista de Usuários
            </a>
            <span class="text-slate-600">&bull;</span>
            <span class="text-xs text-purple-400 font-bold uppercase">Módulo de Gestão</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            <?= $isEdicao ? 'Editar Usuário: ' . htmlspecialchars($usuario['nome']) : 'Cadastrar Novo Colaborador' ?>
        </h1>
        <p class="text-sm text-slate-400">Defina os dados de acesso e as permissões operacionais de armazém.</p>
    </div>

    <!-- Card do Formulário -->
    <div class="max-w-2xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl">
        <form action="actions/usuario_action.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="acao" value="<?= $isEdicao ? 'salvar_edicao' : 'novo' ?>">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

            <!-- Nome Completo -->
            <div>
                <label for="nome" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                    Nome Completo <span class="text-rose-400">*</span>
                </label>
                <input type="text" id="nome" name="nome" required
                       value="<?= htmlspecialchars($usuario['nome']) ?>"
                       placeholder="Ex: Carlos Eduardo Silveira"
                       class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
            </div>

            <!-- E-mail -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                    E-mail Corporativo (Login) <span class="text-rose-400">*</span>
                </label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($usuario['email']) ?>"
                       placeholder="colaborador@nexo.com"
                       class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
            </div>

            <!-- Senha -->
            <div>
                <label for="senha" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                    Senha de Acesso <?= $isEdicao ? '<span class="text-slate-500 font-normal">(Deixe em branco para manter a atual)</span>' : '<span class="text-rose-400">*</span>' ?>
                </label>
                <input type="password" id="senha" name="senha" <?= $isEdicao ? '' : 'required' ?>
                       placeholder="••••••••"
                       class="w-full px-4 py-3 bg-slate-950/70 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
            </div>

            <!-- Perfil de Usuário -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                    Perfil de Acesso <span class="text-rose-400">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-cyan-500/50 transition-colors">
                        <input type="radio" name="perfil" value="funcionario" <?= $usuario['perfil'] === 'funcionario' ? 'checked' : '' ?> onchange="togglePermissoes()" class="text-cyan-600 focus:ring-cyan-500">
                        <div>
                            <span class="text-sm font-bold text-white block">Funcionário Operacional</span>
                            <span class="text-[11px] text-slate-400 block">Consulta estoque e opera conforme permissões</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-purple-500/50 transition-colors">
                        <input type="radio" name="perfil" value="gestor" <?= $usuario['perfil'] === 'gestor' ? 'checked' : '' ?> onchange="togglePermissoes()" class="text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="text-sm font-bold text-white block">Gestor do Armazém</span>
                            <span class="text-[11px] text-slate-400 block">Acesso total e gestão de usuários/produtos</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Permissões Operacionais Granulares -->
            <div id="secao-permissoes" class="p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block mb-3">
                    Permissões de Movimentação do Funcionário
                </span>
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="pode_entrada" value="1" <?= $usuario['pode_entrada'] ? 'checked' : '' ?> class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-sm font-bold text-emerald-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-down text-xs"></i> Permitir Registrar Entradas
                            </span>
                            <span class="text-xs text-slate-400 block">Autoriza o usuário a dar entrada em novos materiais e compras.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="pode_saida" value="1" <?= $usuario['pode_saida'] ? 'checked' : '' ?> class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-bold text-blue-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-up text-xs"></i> Permitir Registrar Saídas / Expedição
                            </span>
                            <span class="text-xs text-slate-400 block">Autoriza o usuário a baixar estoques para separação de pedidos.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                    Situação da Conta
                </label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" value="ativo" <?= $usuario['status'] === 'ativo' ? 'checked' : '' ?> class="text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-emerald-400">Ativo (Acesso Liberado)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" value="inativo" <?= $usuario['status'] === 'inativo' ? 'checked' : '' ?> class="text-rose-600 focus:ring-rose-500">
                        <span class="text-sm font-medium text-rose-400">Inativo (Acesso Bloqueado)</span>
                    </label>
                </div>
            </div>

            <!-- Botões de Envio -->
            <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="usuarios.php" class="w-full sm:w-auto px-5 py-3 rounded-xl text-center text-sm font-semibold text-slate-400 bg-slate-800 hover:bg-slate-700 hover:text-white transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="w-full sm:w-auto px-6 py-3 rounded-xl text-center text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-600/30 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span><?= $isEdicao ? 'Salvar Alterações' : 'Criar Usuário' ?></span>
                </button>
            </div>

        </form>
    </div>

</main>

<script>
function togglePermissoes() {
    const isGestor = document.querySelector('input[name="perfil"]:checked').value === 'gestor';
    const secao = document.getElementById('secao-permissoes');
    if (isGestor) {
        secao.classList.add('opacity-40', 'pointer-events-none');
    } else {
        secao.classList.remove('opacity-40', 'pointer-events-none');
    }
}

document.addEventListener('DOMContentLoaded', togglePermissoes);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
