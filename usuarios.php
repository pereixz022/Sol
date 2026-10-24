<?php
/**
 * NEXO WMS - Gestão de Usuários e Permissões (Restrito a Gestores)
 */
define('PAGE_TITLE', 'Gestão de Usuários');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

requireGestor();

$busca = trim($_GET['busca'] ?? '');
$filtroPerfil = trim($_GET['perfil'] ?? '');

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (nome LIKE :busca OR email LIKE :busca)";
    $params[':busca'] = "%{$busca}%";
}

if (!empty($filtroPerfil) && in_array($filtroPerfil, ['gestor', 'funcionario'])) {
    $sql .= " AND perfil = :perfil";
    $params[':perfil'] = $filtroPerfil;
}

$sql .= " ORDER BY perfil ASC, nome ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    setToast('error', 'Erro ao carregar lista de usuários.');
    $usuarios = [];
}

$currentUser = currentUser();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8">

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase tracking-wider text-purple-400">Controle de Acessos</span>
                <span class="text-slate-600">&bull;</span>
                <span class="text-xs text-slate-400"><?= count($usuarios) ?> usuário(s)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Gestão de Usuários & Permissões
            </h1>
            <p class="text-sm text-slate-400">Cadastre colaboradores, defina perfis de acesso e autorizações para entradas e saídas de estoque.</p>
        </div>

        <div>
            <a href="usuario_form.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-600/25 transition-all">
                <i class="fa-solid fa-user-plus"></i>
                <span>Novo Usuário</span>
            </a>
        </div>
    </div>

    <!-- Filtros de Usuários -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl mb-6">
        <form method="GET" action="usuarios.php" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
                       placeholder="Buscar por nome ou e-mail..."
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
            </div>

            <div>
                <select name="perfil" onchange="this.form.submit()"
                        class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                    <option value="">Todos os Perfis</option>
                    <option value="gestor" <?= $filtroPerfil === 'gestor' ? 'selected' : '' ?>>Somente Gestores</option>
                    <option value="funcionario" <?= $filtroPerfil === 'funcionario' ? 'selected' : '' ?>>Somente Funcionários</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-sm font-bold shadow-md shadow-purple-600/30 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-filter"></i>
                    <span>Filtrar</span>
                </button>
                <?php if (!empty($busca) || !empty($filtroPerfil)): ?>
                    <a href="usuarios.php" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm transition-all">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela Desktop -->
    <div class="hidden md:block bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                <thead class="bg-slate-950/70 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th scope="col" class="py-4 px-6">Usuário</th>
                        <th scope="col" class="py-4 px-6">Perfil</th>
                        <th scope="col" class="py-4 px-6 text-center">Permissão Entrada</th>
                        <th scope="col" class="py-4 px-6 text-center">Permissão Saída</th>
                        <th scope="col" class="py-4 px-6 text-center">Status</th>
                        <th scope="col" class="py-4 px-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($usuarios as $u): ?>
                        <?php 
                            $isSelf = $currentUser['id'] == $u['id'];
                            $isGest = $u['perfil'] === 'gestor';
                            $isAtivo = $u['status'] === 'ativo';
                        ?>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full <?= $isGest ? 'bg-purple-600' : 'bg-cyan-600' ?> flex items-center justify-center text-white font-bold text-sm shadow-md flex-shrink-0">
                                        <?= strtoupper(substr($u['nome'], 0, 2)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-white flex items-center gap-1.5">
                                            <?= htmlspecialchars($u['nome']) ?>
                                            <?php if ($isSelf): ?>
                                                <span class="text-[10px] bg-slate-800 text-cyan-300 border border-cyan-800 px-1.5 py-0.2 rounded font-mono">Você</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase <?= $isGest ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-cyan-950 text-cyan-300 border border-cyan-800' ?>">
                                    <i class="fa-solid <?= $isGest ? 'fa-user-shield' : 'fa-user' ?>"></i>
                                    <?= $isGest ? 'Gestor' : 'Funcionário' ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <?php if ($isGest || $u['pode_entrada']): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                        <i class="fa-solid fa-check text-xs"></i> Liberada
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                        <i class="fa-solid fa-ban text-xs"></i> Bloqueada
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <?php if ($isGest || $u['pode_saida']): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-950 text-blue-300 border border-blue-800">
                                        <i class="fa-solid fa-check text-xs"></i> Liberada
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                        <i class="fa-solid fa-ban text-xs"></i> Bloqueada
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold <?= $isAtivo ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $isAtivo ? 'bg-emerald-400' : 'bg-rose-400' ?>"></span>
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="usuario_form.php?id=<?= $u['id'] ?>" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs transition-colors" title="Editar Usuário & Permissões">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </a>

                                    <?php if (!$isSelf): ?>
                                        <!-- Alternar Status (Ativar / Desativar) via Modal Customizado -->
                                        <button type="button"
                                                data-confirm="Deseja <?= $isAtivo ? 'DESATIVAR' : 'ATIVAR' ?> o acesso do usuário '<?= htmlspecialchars(addslashes($u['nome'])) ?>'?"
                                                data-title="<?= $isAtivo ? 'Desativar Usuário' : 'Ativar Usuário' ?>"
                                                data-type="<?= $isAtivo ? 'danger' : 'primary' ?>"
                                                data-btn-text="Sim, <?= $isAtivo ? 'Desativar' : 'Ativar' ?>"
                                                href="actions/usuario_action.php?acao=toggle_status&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                                class="p-2 rounded-lg <?= $isAtivo ? 'bg-amber-950/60 hover:bg-amber-900 border-amber-800 text-amber-300' : 'bg-emerald-950/60 hover:bg-emerald-900 border-emerald-800 text-emerald-300' ?> border text-xs transition-colors"
                                                title="<?= $isAtivo ? 'Desativar' : 'Ativar' ?>">
                                            <i class="fa-solid <?= $isAtivo ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>

                                        <!-- Excluir Usuário via Modal Customizado -->
                                        <button type="button"
                                                data-confirm="Tem certeza que deseja EXCLUIR permanentemente o usuário '<?= htmlspecialchars(addslashes($u['nome'])) ?>'?"
                                                data-title="Excluir Usuário"
                                                data-type="danger"
                                                data-btn-text="Sim, Excluir"
                                                href="actions/usuario_action.php?acao=excluir&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                                class="p-2 rounded-lg bg-rose-950/60 hover:bg-rose-900 border border-rose-800 text-rose-300 text-xs transition-colors"
                                                title="Excluir Usuário">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cards Mobile (Mobile-First) -->
    <div class="grid grid-cols-1 gap-4 md:hidden">
        <?php foreach ($usuarios as $u): ?>
            <?php 
                $isSelf = $currentUser['id'] == $u['id'];
                $isGest = $u['perfil'] === 'gestor';
                $isAtivo = $u['status'] === 'ativo';
            ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-full <?= $isGest ? 'bg-purple-600' : 'bg-cyan-600' ?> flex items-center justify-center text-white font-bold text-sm">
                            <?= strtoupper(substr($u['nome'], 0, 2)) ?>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                                <?= htmlspecialchars($u['nome']) ?>
                                <?php if ($isSelf): ?>
                                    <span class="text-[9px] bg-slate-800 text-cyan-300 border border-cyan-800 px-1 rounded">Você</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full <?= $isAtivo ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' ?>">
                        <?= ucfirst($u['status']) ?>
                    </span>
                </div>

                <!-- Detalhes e Permissões -->
                <div class="p-3 bg-slate-950/70 rounded-xl border border-slate-800 grid grid-cols-2 gap-2 text-xs mb-4">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Perfil de Acesso</span>
                        <span class="font-bold <?= $isGest ? 'text-purple-300' : 'text-cyan-300' ?>"><?= $isGest ? 'Gestor' : 'Funcionário' ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Operações</span>
                        <div class="flex items-center gap-1 mt-0.5">
                            <span class="px-1.5 py-0.2 rounded text-[10px] <?= ($isGest || $u['pode_entrada']) ? 'bg-emerald-950 text-emerald-300' : 'bg-slate-800 text-slate-500' ?>">Entrada</span>
                            <span class="px-1.5 py-0.2 rounded text-[10px] <?= ($isGest || $u['pode_saida']) ? 'bg-blue-950 text-blue-300' : 'bg-slate-800 text-slate-500' ?>">Saída</span>
                        </div>
                    </div>
                </div>

                <!-- Ações Mobile -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <a href="usuario_form.php?id=<?= $u['id'] ?>" class="flex-1 text-center py-2 px-3 rounded-xl bg-slate-800 text-slate-200 text-xs font-semibold hover:bg-slate-700">
                        Editar Dados
                    </a>

                    <?php if (!$isSelf): ?>
                        <button type="button"
                                data-confirm="Deseja <?= $isAtivo ? 'DESATIVAR' : 'ATIVAR' ?> o usuário '<?= htmlspecialchars(addslashes($u['nome'])) ?>'?"
                                data-title="<?= $isAtivo ? 'Desativar Usuário' : 'Ativar Usuário' ?>"
                                data-type="<?= $isAtivo ? 'danger' : 'primary' ?>"
                                data-btn-text="Sim, <?= $isAtivo ? 'Desativar' : 'Ativar' ?>"
                                href="actions/usuario_action.php?acao=toggle_status&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                class="p-2 rounded-xl <?= $isAtivo ? 'bg-amber-950/60 text-amber-300 border-amber-800' : 'bg-emerald-950/60 text-emerald-300 border-emerald-800' ?> border text-xs">
                            <i class="fa-solid <?= $isAtivo ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                        </button>

                        <button type="button"
                                data-confirm="Tem certeza que deseja EXCLUIR o usuário '<?= htmlspecialchars(addslashes($u['nome'])) ?>'?"
                                data-title="Excluir Usuário"
                                data-type="danger"
                                data-btn-text="Sim, Excluir"
                                href="actions/usuario_action.php?acao=excluir&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                class="p-2 rounded-xl bg-rose-950/60 text-rose-300 border border-rose-800 text-xs">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
