<?php
/**
 * NEXO WMS - Processamento Transacional de Movimentações (Entrada / Saída)
 */
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

// 1. Verificação de Token CSRF
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf($token)) {
    setToast('error', 'Token de autenticação de formulário inválido.');
    header('Location: ../dashboard.php');
    exit;
}

$tipo        = strtolower(trim($_POST['tipo'] ?? ''));
$produto_id  = (int)($_POST['produto_id'] ?? 0);
$quantidade  = (int)($_POST['quantidade'] ?? 0);
$motivo      = trim($_POST['motivo'] ?? '');
$documento   = trim($_POST['documento'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

// 2. Validação do Tipo e Permissões do Usuário
if ($tipo !== 'entrada' && $tipo !== 'saida') {
    setToast('error', 'Tipo de movimentação inválido.');
    header('Location: ../dashboard.php');
    exit;
}

if ($tipo === 'entrada' && !canRegisterEntrada()) {
    setToast('error', 'Você não tem permissão para registrar entradas de materiais.');
    header('Location: ../dashboard.php');
    exit;
}

if ($tipo === 'saida' && !canRegisterSaida()) {
    setToast('error', 'Você não tem permissão para registrar saídas de materiais.');
    header('Location: ../dashboard.php');
    exit;
}

// 3. Validação dos Campos Obrigatórios
if ($produto_id <= 0 || $quantidade <= 0 || empty($motivo)) {
    setToast('warning', 'Informe o produto, quantidade válida (maior que 0) e o motivo da operação.');
    header("Location: ../movimentacao.php?tipo={$tipo}&produto_id={$produto_id}");
    exit;
}

$user = currentUser();

try {
    // 4. Execução Atômica usando Transação PDO para Segurança Total
    $pdo->beginTransaction();

    // Consulta o produto atual com lock para evitar condições de corrida (Race Condition)
    $stmtProd = $pdo->prepare("SELECT id, sku, nome, quantidade, unidade, status FROM produtos WHERE id = :id LIMIT 1");
    $stmtProd->execute([':id' => $produto_id]);
    $produto = $stmtProd->fetch();

    if (!$produto) {
        $pdo->rollBack();
        setToast('error', 'O produto selecionado não foi encontrado no armazém.');
        header("Location: ../movimentacao.php?tipo={$tipo}");
        exit;
    }

    if ($produto['status'] !== 'ativo') {
        $pdo->rollBack();
        setToast('error', 'Este produto está inativo e não pode receber movimentações.');
        header("Location: ../movimentacao.php?tipo={$tipo}");
        exit;
    }

    $saldoAnterior = (int)$produto['quantidade'];

    if ($tipo === 'saida') {
        // Regra de Negócio: Saldo não pode ficar negativo
        if ($quantidade > $saldoAnterior) {
            $pdo->rollBack();
            setToast('error', "Saldo insuficiente para saída! Disponível em estoque: {$saldoAnterior} {$produto['unidade']}, solicitado: {$quantidade} {$produto['unidade']}.", 'Estoque Insuficiente');
            header("Location: ../movimentacao.php?tipo=saida&produto_id={$produto_id}");
            exit;
        }
        $saldoPosterior = $saldoAnterior - $quantidade;
    } else {
        $saldoPosterior = $saldoAnterior + $quantidade;
    }

    // Atualiza o saldo do produto no armazém
    $stmtUpdate = $pdo->prepare("UPDATE produtos SET quantidade = :novo_saldo WHERE id = :id");
    $stmtUpdate->execute([
        ':novo_saldo' => $saldoPosterior,
        ':id'         => $produto_id
    ]);

    // Registra a movimentação detalhada no histórico de auditoria
    $stmtMov = $pdo->prepare("
        INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, documento, observacoes)
        VALUES (:prod, :user, :tipo, :qtd, :ant, :pos, :motivo, :doc, :obs)
    ");
    $stmtMov->execute([
        ':prod'   => $produto_id,
        ':user'   => $user['id'],
        ':tipo'   => $tipo,
        ':qtd'    => $quantidade,
        ':ant'    => $saldoAnterior,
        ':pos'    => $saldoPosterior,
        ':motivo' => $motivo,
        ':doc'    => $documento ?: null,
        ':obs'    => $observacoes ?: null
    ]);

    // Confirma a transação atômica
    $pdo->commit();

    $tipoNome = $tipo === 'entrada' ? 'Entrada' : 'Saída';
    setToast('success', "{$tipoNome} de {$quantidade} {$produto['unidade']} de '{$produto['nome']}' registrada com sucesso! Novo saldo: {$saldoPosterior} {$produto['unidade']}.", 'Movimentação Concluída');
    header('Location: ../historico.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setToast('error', 'Falha ao registrar movimentação no banco de dados: ' . $e->getMessage());
    header("Location: ../movimentacao.php?tipo={$tipo}&produto_id={$produto_id}");
    exit;
}
