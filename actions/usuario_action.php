<?php
/**
 * NEXO WMS - Processamento de Ações de Usuários e Permissões
 */
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

requireGestor();

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    setToast('error', 'Token de autenticação inválido. Operação abortada.');
    header('Location: ../usuarios.php');
    exit;
}

$currentUser = currentUser();

if ($acao === 'novo') {
    $nome         = trim($_POST['nome'] ?? '');
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $senha        = trim($_POST['senha'] ?? '');
    $perfil       = ($_POST['perfil'] ?? 'funcionario') === 'gestor' ? 'gestor' : 'funcionario';
    $pode_entrada = isset($_POST['pode_entrada']) ? 1 : 0;
    $pode_saida   = isset($_POST['pode_saida']) ? 1 : 0;
    $status       = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    // Se perfil for gestor, garante permissões totais
    if ($perfil === 'gestor') {
        $pode_entrada = 1;
        $pode_saida   = 1;
    }

    if (empty($nome) || empty($email) || empty($senha)) {
        setToast('warning', 'Nome, e-mail e senha são obrigatórios para cadastro.');
        header('Location: ../usuario_form.php');
        exit;
    }

    try {
        // Verifica se e-mail já existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            setToast('error', "O e-mail '{$email}' já está em uso por outro usuário.");
            header('Location: ../usuario_form.php');
            exit;
        }

        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, perfil, pode_entrada, pode_saida, status)
            VALUES (:nome, :email, :senha, :perfil, :pe, :ps, :status)
        ");
        $stmt->execute([
            ':nome'   => $nome,
            ':email'  => $email,
            ':senha'  => $hashSenha,
            ':perfil' => $perfil,
            ':pe'     => $pode_entrada,
            ':ps'     => $pode_saida,
            ':status' => $status
        ]);

        setToast('success', "Usuário '{$nome}' cadastrado com sucesso!", 'Colaborador Criado');
        header('Location: ../usuarios.php');
        exit;

    } catch (PDOException $e) {
        setToast('error', 'Falha ao cadastrar usuário: ' . $e->getMessage());
        header('Location: ../usuario_form.php');
        exit;
    }

} elseif ($acao === 'salvar_edicao') {
    $id           = (int)($_POST['id'] ?? 0);
    $nome         = trim($_POST['nome'] ?? '');
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $senha        = trim($_POST['senha'] ?? '');
    $perfil       = ($_POST['perfil'] ?? 'funcionario') === 'gestor' ? 'gestor' : 'funcionario';
    $pode_entrada = isset($_POST['pode_entrada']) ? 1 : 0;
    $pode_saida   = isset($_POST['pode_saida']) ? 1 : 0;
    $status       = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    if ($id <= 0 || empty($nome) || empty($email)) {
        setToast('warning', 'Dados incompletos para atualização.');
        header('Location: ../usuarios.php');
        exit;
    }

    if ($perfil === 'gestor') {
        $pode_entrada = 1;
        $pode_saida   = 1;
    }

    // Regra de segurança: O gestor não pode desativar seu próprio usuário
    if ($currentUser['id'] == $id && $status === 'inativo') {
        setToast('error', 'Você não pode desativar o seu próprio usuário logado.');
        header("Location: ../usuario_form.php?id={$id}");
        exit;
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1");
        $stmtCheck->execute([':email' => $email, ':id' => $id]);
        if ($stmtCheck->fetch()) {
            setToast('error', "O e-mail '{$email}' já está associado a outro usuário.");
            header("Location: ../usuario_form.php?id={$id}");
            exit;
        }

        if (!empty($senha)) {
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome = :nome, email = :email, senha = :senha, perfil = :perfil, pode_entrada = :pe, pode_saida = :ps, status = :status
                WHERE id = :id
            ");
            $params = [
                ':nome'   => $nome,
                ':email'  => $email,
                ':senha'  => $hashSenha,
                ':perfil' => $perfil,
                ':pe'     => $pode_entrada,
                ':ps'     => $pode_saida,
                ':status' => $status,
                ':id'     => $id
            ];
        } else {
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome = :nome, email = :email, perfil = :perfil, pode_entrada = :pe, pode_saida = :ps, status = :status
                WHERE id = :id
            ");
            $params = [
                ':nome'   => $nome,
                ':email'  => $email,
                ':perfil' => $perfil,
                ':pe'     => $pode_entrada,
                ':ps'     => $pode_saida,
                ':status' => $status,
                ':id'     => $id
            ];
        }

        $stmt->execute($params);

        // Se for o próprio usuário logado, atualiza nome na sessão
        if ($currentUser['id'] == $id) {
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_email'] = $email;
        }

        setToast('success', "Dados e permissões de '{$nome}' atualizados com sucesso!");
        header('Location: ../usuarios.php');
        exit;

    } catch (PDOException $e) {
        setToast('error', 'Erro ao salvar alterações do usuário.');
        header("Location: ../usuario_form.php?id={$id}");
        exit;
    }

} elseif ($acao === 'toggle_status') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0 || $currentUser['id'] == $id) {
        setToast('error', 'Ação não permitida para este usuário.');
        header('Location: ../usuarios.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT nome, status FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            setToast('error', 'Usuário não encontrado.');
            header('Location: ../usuarios.php');
            exit;
        }

        $novoStatus = $user['status'] === 'ativo' ? 'inativo' : 'ativo';
        $stmtUp = $pdo->prepare("UPDATE usuarios SET status = :status WHERE id = :id");
        $stmtUp->execute([':status' => $novoStatus, ':id' => $id]);

        $msg = $novoStatus === 'ativo' ? 'ativado com sucesso!' : 'desativado com sucesso!';
        setToast('success', "O usuário '{$user['nome']}' foi {$msg}");
        header('Location: ../usuarios.php');
        exit;

    } catch (PDOException $e) {
        setToast('error', 'Erro ao alterar status do usuário.');
        header('Location: ../usuarios.php');
        exit;
    }

} elseif ($acao === 'excluir') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0 || $currentUser['id'] == $id) {
        setToast('error', 'Você não pode excluir o seu próprio usuário logado.');
        header('Location: ../usuarios.php');
        exit;
    }

    try {
        // Verifica se o usuário tem movimentações registradas
        $stmtMov = $pdo->prepare("SELECT COUNT(*) as total FROM movimentacoes WHERE usuario_id = :id");
        $stmtMov->execute([':id' => $id]);
        $totalMovs = (int)$stmtMov->fetch()['total'];

        if ($totalMovs > 0) {
            // Se tiver movimentações, apenas desativa para manter a auditoria
            $stmtDesativa = $pdo->prepare("UPDATE usuarios SET status = 'inativo' WHERE id = :id");
            $stmtDesativa->execute([':id' => $id]);
            setToast('warning', "O usuário possui {$totalMovs} movimentações registradas no armazém. Para preservar a auditoria, o usuário foi DESATIVADO.", 'Usuário Desativado');
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmtDel->execute([':id' => $id]);
            setToast('success', 'Usuário excluído permanentemente.');
        }

        header('Location: ../usuarios.php');
        exit;

    } catch (PDOException $e) {
        setToast('error', 'Erro ao excluir o usuário.');
        header('Location: ../usuarios.php');
        exit;
    }
}

header('Location: ../usuarios.php');
exit;
