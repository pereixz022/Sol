<?php
/**
 * NEXO WMS - Processamento de Ações de Produtos
 */
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

requireGestor();

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    setToast('error', 'Token de segurança inválido. Ação cancelada.');
    header('Location: ../produtos.php');
    exit;
}

if ($acao === 'novo') {
    $sku           = strtoupper(trim($_POST['sku'] ?? ''));
    $nome          = trim($_POST['nome'] ?? '');
    $categoria_id  = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $localizacao   = trim($_POST['localizacao'] ?? '');
    $unidade       = trim($_POST['unidade'] ?? 'UN');
    $preco         = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
    $quantidade    = max(0, (int)($_POST['quantidade'] ?? 0));
    $estoque_min   = max(0, (int)($_POST['estoque_minimo'] ?? 10));
    $status        = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    if (empty($sku) || empty($nome) || empty($localizacao)) {
        setToast('warning', 'Preencha todos os campos obrigatórios (SKU, Nome, Localização).');
        header('Location: ../produto_form.php');
        exit;
    }

    try {
        // Verifica duplicidade de SKU
        $stmtCheck = $pdo->prepare("SELECT id FROM produtos WHERE sku = :sku LIMIT 1");
        $stmtCheck->execute([':sku' => $sku]);
        if ($stmtCheck->fetch()) {
            setToast('error', "O código SKU '{$sku}' já está cadastrado em outro produto.");
            header('Location: ../produto_form.php');
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO produtos (sku, nome, categoria_id, localizacao, unidade, preco, quantidade, estoque_minimo, status)
            VALUES (:sku, :nome, :cat, :loc, :un, :preco, :qtd, :min, :status)
        ");
        $stmt->execute([
            ':sku'    => $sku,
            ':nome'   => $nome,
            ':cat'    => $categoria_id,
            ':loc'    => $localizacao,
            ':un'     => $unidade,
            ':preco'  => $preco,
            ':qtd'    => $quantidade,
            ':min'    => $estoque_min,
            ':status' => $status
        ]);
        $novoId = $pdo->lastInsertId();

        // Se iniciou com estoque maior que 0, registra a carga inicial
        if ($quantidade > 0) {
            $user = currentUser();
            $stmtMov = $pdo->prepare("
                INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, documento, observacoes)
                VALUES (:prod, :user, 'entrada', :qtd, 0, :qtd, 'Carga de Cadastro Inicial', 'CADASTRO', 'Inventário inicial do material cadastrado pelo Gestor.')
            ");
            $stmtMov->execute([
                ':prod' => $novoId,
                ':user' => $user['id'],
                ':qtd'  => $quantidade
            ]);
        }

        $pdo->commit();
        setToast('success', "Produto '{$nome}' cadastrado com sucesso!", 'Sucesso');
        header('Location: ../produtos.php');
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setToast('error', 'Erro ao salvar o novo produto: ' . $e->getMessage());
        header('Location: ../produto_form.php');
        exit;
    }

} elseif ($acao === 'salvar_edicao') {
    $id            = (int)($_POST['id'] ?? 0);
    $sku           = strtoupper(trim($_POST['sku'] ?? ''));
    $nome          = trim($_POST['nome'] ?? '');
    $categoria_id  = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $localizacao   = trim($_POST['localizacao'] ?? '');
    $unidade       = trim($_POST['unidade'] ?? 'UN');
    $preco         = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
    $estoque_min   = max(0, (int)($_POST['estoque_minimo'] ?? 10));
    $status        = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    if ($id <= 0 || empty($sku) || empty($nome) || empty($localizacao)) {
        setToast('warning', 'Dados incompletos para atualização do produto.');
        header('Location: ../produtos.php');
        exit;
    }

    try {
        // Verifica se o SKU pertence a outro produto
        $stmtCheck = $pdo->prepare("SELECT id FROM produtos WHERE sku = :sku AND id != :id LIMIT 1");
        $stmtCheck->execute([':sku' => $sku, ':id' => $id]);
        if ($stmtCheck->fetch()) {
            setToast('error', "O código SKU '{$sku}' já está em uso por outro produto.");
            header("Location: ../produto_form.php?id={$id}");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE produtos 
            SET sku = :sku, nome = :nome, categoria_id = :cat, localizacao = :loc, unidade = :un, preco = :preco, estoque_minimo = :min, status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            ':sku'    => $sku,
            ':nome'   => $nome,
            ':cat'    => $categoria_id,
            ':loc'    => $localizacao,
            ':un'     => $unidade,
            ':preco'  => $preco,
            ':min'    => $estoque_min,
            ':status' => $status,
            ':id'     => $id
        ]);

        setToast('success', "Informações do produto '{$nome}' atualizadas com sucesso!");
        header('Location: ../produtos.php');
        exit;

    } catch (PDOException $e) {
        setToast('error', 'Erro ao atualizar o produto.');
        header("Location: ../produto_form.php?id={$id}");
        exit;
    }

} elseif ($acao === 'excluir') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        setToast('error', 'Produto inválido para remoção.');
        header('Location: ../produtos.php');
        exit;
    }

    try {
        // Verifica se existem movimentações associadas a este produto
        $stmtMov = $pdo->prepare("SELECT COUNT(*) as total FROM movimentacoes WHERE produto_id = :id");
        $stmtMov->execute([':id' => $id]);
        $totalMovs = (int)$stmtMov->fetch()['total'];

        if ($totalMovs > 0) {
            // Preservação de integridade histórica: desativa o produto
            $stmtDesativa = $pdo->prepare("UPDATE produtos SET status = 'inativo' WHERE id = :id");
            $stmtDesativa->execute([':id' => $id]);
            setToast('warning', "O produto possui {$totalMovs} movimentações registradas no histórico. Para preservar a auditoria, o produto foi DESATIVADO.", 'Produto Desativado');
        } else {
            // Sem histórico: pode excluir definitivamente
            $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
            $stmtDel->execute([':id' => $id]);
            setToast('success', 'Produto removido permanentemente do armazém.');
        }

        header('Location: ../produtos.php');
        exit;

    } catch (PDOException $e) {
        setToast('error', 'Erro ao excluir o produto.');
        header('Location: ../produtos.php');
        exit;
    }
}

header('Location: ../produtos.php');
exit;
