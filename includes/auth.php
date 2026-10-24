<?php
/**
 * NEXO WMS - Gerenciamento de Sessão, Autenticação e Controle de Acesso
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna se o usuário está autenticado
 */
function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Retorna os dados do usuário autenticado na sessão
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'           => $_SESSION['usuario_id'],
        'nome'         => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email'        => $_SESSION['usuario_email'] ?? '',
        'perfil'       => $_SESSION['usuario_perfil'] ?? 'funcionario',
        'pode_entrada' => (int)($_SESSION['usuario_pode_entrada'] ?? 0),
        'pode_saida'   => (int)($_SESSION['usuario_pode_saida'] ?? 0),
    ];
}

/**
 * Exige que o usuário esteja logado para acessar a página
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setToast('error', 'Faça login para acessar o sistema.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Verifica se o usuário autenticado possui perfil de Gestor
 */
function isGestor(): bool {
    return isLoggedIn() && (($_SESSION['usuario_perfil'] ?? '') === 'gestor');
}

/**
 * Exige que o usuário autenticado seja Gestor
 */
function requireGestor(): void {
    requireLogin();
    if (!isGestor()) {
        setToast('error', 'Acesso restrito. Seu perfil não possui permissão de Gestor para acessar este recurso.');
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Verifica se o usuário tem permissão para registrar Entradas
 */
function canRegisterEntrada(): bool {
    if (!isLoggedIn()) return false;
    if (isGestor()) return true;
    return !empty($_SESSION['usuario_pode_entrada']);
}

/**
 * Verifica se o usuário tem permissão para registrar Saídas
 */
function canRegisterSaida(): bool {
    if (!isLoggedIn()) return false;
    if (isGestor()) return true;
    return !empty($_SESSION['usuario_pode_saida']);
}

/**
 * Registra uma mensagem Toast na sessão para exibição ao usuário
 * @param string $type success | error | warning | info
 */
function setToast(string $type, string $message, string $title = ''): void {
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    $_SESSION['toasts'][] = [
        'type'    => $type,
        'message' => $message,
        'title'   => $title,
        'id'      => uniqid('toast_')
    ];
}

/**
 * Recupera e remove as mensagens Toast da sessão
 */
function getToasts(): array {
    $toasts = $_SESSION['toasts'] ?? [];
    unset($_SESSION['toasts']);
    return $toasts;
}

/**
 * Gera e recupera token CSRF para formulários
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF recebido na requisição
 */
function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
