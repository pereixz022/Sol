<?php
/**
 * NEXO WMS - Processamento de Autenticação
 */
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Validação do Token CSRF
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf($token)) {
    setToast('error', 'Token de segurança inválido ou expirado. Tente novamente.');
    header('Location: ../login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if (empty($email) || empty($senha)) {
    setToast('warning', 'Por favor, preencha todos os campos para entrar.');
    header('Location: ../login.php');
    exit;
}

try {
    // Consulta segura usando Prepared Statements com PDO
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, perfil, pode_entrada, pode_saida, status FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        setToast('error', 'E-mail ou senha incorretos.');
        header('Location: ../login.php');
        exit;
    }

    // Validação da senha
    if (!password_verify($senha, $usuario['senha'])) {
        setToast('error', 'E-mail ou senha incorretos.');
        header('Location: ../login.php');
        exit;
    }

    // Validação se o usuário está ativo
    if ($usuario['status'] !== 'ativo') {
        setToast('error', 'Acesso negado: Este usuário foi desativado pelo Gestor do armazém.');
        header('Location: ../login.php');
        exit;
    }

    // Login bem-sucedido: inicializa variáveis de sessão nativas
    $_SESSION['usuario_id']           = $usuario['id'];
    $_SESSION['usuario_nome']         = $usuario['nome'];
    $_SESSION['usuario_email']        = $usuario['email'];
    $_SESSION['usuario_perfil']       = $usuario['perfil'];
    $_SESSION['usuario_pode_entrada'] = (int)$usuario['pode_entrada'];
    $_SESSION['usuario_pode_saida']   = (int)$usuario['pode_saida'];

    setToast('success', 'Bem-vindo de volta, ' . htmlspecialchars($usuario['nome']) . '!', 'Login Realizado');
    header('Location: ../dashboard.php');
    exit;

} catch (PDOException $e) {
    setToast('error', 'Erro interno de banco de dados ao autenticar. Tente novamente.');
    header('Location: ../login.php');
    exit;
}
