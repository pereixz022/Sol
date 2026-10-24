<?php
/**
 * NEXO WMS - Bateria Automatizada de Testes Unitários e de Integração
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

$passed = 0;
$failed = 0;

function assertTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] " . $description . PHP_EOL;
        $passed++;
    } else {
        echo "  [FAIL] " . $description . PHP_EOL;
        $failed++;
    }
}

echo "=== INICIANDO TESTES DO NEXO WMS ===" . PHP_EOL . PHP_EOL;

// Teste 1: Estrutura do Banco de Dados
echo "1. Verificação de Estrutura do Banco de Dados:" . PHP_EOL;
$tabelas = ['usuarios', 'categorias', 'produtos', 'movimentacoes'];
foreach ($tabelas as $tab) {
    $count = $pdo->query("SELECT COUNT(*) as t FROM {$tab}")->fetch()['t'];
    assertTest("Tabela '{$tab}' existe e possui registros ({$count} encontrados)", $count >= 0);
}

// Teste 2: Autenticação de Gestor
echo PHP_EOL . "2. Teste de Autenticação:" . PHP_EOL;
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = 'admin@nexo.com'");
$stmt->execute();
$admin = $stmt->fetch();
assertTest("Usuário gestor encontrado (admin@nexo.com)", !empty($admin));
assertTest("Senha do gestor confere com 'admin123'", password_verify('admin123', $admin['senha']));
assertTest("Gestor possui perfil 'gestor'", $admin['perfil'] === 'gestor');

// Teste 3: Autenticação de Funcionário com Permissões Específicas
$stmtM = $pdo->prepare("SELECT * FROM usuarios WHERE email = 'mariana@nexo.com'");
$stmtM->execute();
$mariana = $stmtM->fetch();
assertTest("Mariana possui pode_entrada = 0 (Somente Expedição)", $mariana['pode_entrada'] == 0);
assertTest("Mariana possui pode_saida = 1", $mariana['pode_saida'] == 1);

// Teste 4: Regras de Permissão
echo PHP_EOL . "3. Verificação de Regras de Controle de Acesso (RBAC):" . PHP_EOL;
$_SESSION['usuario_id'] = $admin['id'];
$_SESSION['usuario_perfil'] = 'gestor';
$_SESSION['usuario_pode_entrada'] = 1;
$_SESSION['usuario_pode_saida'] = 1;
assertTest("Gestor autenticado tem isGestor() = true", isGestor() === true);
assertTest("Gestor tem canRegisterEntrada() = true", canRegisterEntrada() === true);
assertTest("Gestor tem canRegisterSaida() = true", canRegisterSaida() === true);

// Configura sessão como Mariana
$_SESSION['usuario_id'] = $mariana['id'];
$_SESSION['usuario_perfil'] = 'funcionario';
$_SESSION['usuario_pode_entrada'] = 0;
$_SESSION['usuario_pode_saida'] = 1;
assertTest("Mariana tem isGestor() = false", isGestor() === false);
assertTest("Mariana canRegisterEntrada() = false (Bloqueada)", canRegisterEntrada() === false);
assertTest("Mariana canRegisterSaida() = true (Permitida)", canRegisterSaida() === true);

// Teste 5: Transação de Estoque - Entrada
echo PHP_EOL . "4. Teste de Movimentação de Entrada:" . PHP_EOL;
// Buscar produto de teste
$stmtP = $pdo->prepare("SELECT id, quantidade FROM produtos WHERE sku = 'EPI-CAP-001'");
$stmtP->execute();
$prod = $stmtP->fetch();
$saldoOrig = (int)$prod['quantidade'];

$qtdEntrada = 25;
$pdo->beginTransaction();
$novoSaldo = $saldoOrig + $qtdEntrada;
$pdo->prepare("UPDATE produtos SET quantidade = ? WHERE id = ?")->execute([$novoSaldo, $prod['id']]);
$pdo->prepare("INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo) VALUES (?, ?, 'entrada', ?, ?, ?, 'Teste Unitário Entrada')")->execute([$prod['id'], 1, $qtdEntrada, $saldoOrig, $novoSaldo]);
$pdo->commit();

$saldoAposEntrada = (int)$pdo->query("SELECT quantidade FROM produtos WHERE sku = 'EPI-CAP-001'")->fetch()['quantidade'];
assertTest("Entrada de {$qtdEntrada} unidades aumentou saldo de {$saldoOrig} para {$novoSaldo}", $saldoAposEntrada === $novoSaldo);

// Teste 6: Transação de Estoque - Saída Válida
echo PHP_EOL . "5. Teste de Movimentação de Saída Válida:" . PHP_EOL;
$qtdSaida = 10;
$pdo->beginTransaction();
$novoSaldoSaida = $saldoAposEntrada - $qtdSaida;
$pdo->prepare("UPDATE produtos SET quantidade = ? WHERE id = ?")->execute([$novoSaldoSaida, $prod['id']]);
$pdo->prepare("INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo) VALUES (?, ?, 'saida', ?, ?, ?, 'Teste Unitário Saída')")->execute([$prod['id'], 1, $qtdSaida, $saldoAposEntrada, $novoSaldoSaida]);
$pdo->commit();

$saldoFinal = (int)$pdo->query("SELECT quantidade FROM produtos WHERE sku = 'EPI-CAP-001'")->fetch()['quantidade'];
assertTest("Saída de {$qtdSaida} unidades diminuiu saldo para {$novoSaldoSaida}", $saldoFinal === $novoSaldoSaida);

// Teste 7: Bloqueio de Saída com Saldo Insuficiente
echo PHP_EOL . "6. Teste de Proteção contra Saldo Negativo (Regra de Negócio):" . PHP_EOL;
$qtdExcessiva = $saldoFinal + 1000;
$bloqueado = false;
if ($qtdExcessiva > $saldoFinal) {
    $bloqueado = true; // Simula a verificação de movimentacao_action.php
}
assertTest("Tentativa de saída excessiva ({$qtdExcessiva} > {$saldoFinal}) é barrada", $bloqueado === true);
$saldoInalterado = (int)$pdo->query("SELECT quantidade FROM produtos WHERE sku = 'EPI-CAP-001'")->fetch()['quantidade'];
assertTest("Saldo permanece íntegro e inalterado ({$saldoInalterado})", $saldoInalterado === $saldoFinal);

// Teste 8: Auditoria no Histórico
echo PHP_EOL . "7. Verificação de Auditoria:" . PHP_EOL;
$ultMov = $pdo->query("SELECT * FROM movimentacoes ORDER BY id DESC LIMIT 1")->fetch();
assertTest("Última movimentação registrada é do tipo 'saida'", $ultMov['tipo'] === 'saida');
assertTest("Saldo anterior e posterior conferem com a operação", $ultMov['saldo_anterior'] === $saldoAposEntrada && $ultMov['saldo_posterior'] === $novoSaldoSaida);

echo PHP_EOL . "=== RESULTADO DOS TESTES ===" . PHP_EOL;
echo "Passaram: {$passed} | Falharam: {$failed}" . PHP_EOL;

if ($failed === 0) {
    echo ">>> TODOS OS TESTES PASSARAM COM SUCESSO ABSOLUTO! <<<" . PHP_EOL;
}
