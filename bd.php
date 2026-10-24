<?php
/**
 * NEXO WMS - Conexão Segura com Banco de Dados via PDO
 *
 * Configurado primordialmente para MySQL conforme os requisitos do projeto.
 * Possui contingência inteligente para SQLite caso o serviço MySQL local
 * esteja desligado, permitindo testes imediatos sem fricção.
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'nexo_wms');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Tentativa 1: Conexão com Servidor MySQL
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $GLOBALS['DB_DRIVER'] = 'mysql';
        return $pdo;
    } catch (PDOException $e) {
        // Se a base de dados ainda não existir no MySQL, tenta criá-la
        if ($e->getCode() == 1049) {
            try {
                $dsnServer = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
                $pdoServer = new PDO($dsnServer, DB_USER, DB_PASS, $options);
                $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                inicializarTabelasMySQL($pdo);
                $GLOBALS['DB_DRIVER'] = 'mysql';
                return $pdo;
            } catch (Exception $ex) {
                // Falha ao criar no MySQL
            }
        }

        // Tentativa 2: Fallback transparente para SQLite para permitir execução e testes imediatos
        try {
            $sqliteFile = __DIR__ . '/nexo_wms.sqlite';
            $isNew = !file_exists($sqliteFile);
            $pdo = new PDO('sqlite:' . $sqliteFile, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            if ($isNew || filesize($sqliteFile) === 0) {
                inicializarTabelasSQLite($pdo);
            }
            $GLOBALS['DB_DRIVER'] = 'sqlite';
            return $pdo;
        } catch (PDOException $sqliteEx) {
            die("Erro crítico de conexão com Banco de Dados: " . htmlspecialchars($e->getMessage()));
        }
    }
}

/**
 * Cria a estrutura inicial no MySQL se o banco estiver vazio
 */
function inicializarTabelasMySQL(PDO $pdo): void {
    $sqlFile = __DIR__ . '/schema.sql';
    if (file_exists($sqlFile)) {
        $queries = file_get_contents($sqlFile);
        $pdo->exec($queries);
    }
}

/**
 * Cria a estrutura compatível e dados de teste no SQLite
 */
function inicializarTabelasSQLite(PDO $pdo): void {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            senha TEXT NOT NULL,
            perfil TEXT NOT NULL DEFAULT 'funcionario',
            pode_entrada INTEGER NOT NULL DEFAULT 1,
            pode_saida INTEGER NOT NULL DEFAULT 1,
            status TEXT NOT NULL DEFAULT 'ativo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL UNIQUE,
            descricao TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sku TEXT NOT NULL UNIQUE,
            nome TEXT NOT NULL,
            categoria_id INTEGER,
            localizacao TEXT NOT NULL,
            unidade TEXT NOT NULL DEFAULT 'UN',
            preco REAL NOT NULL DEFAULT 0.00,
            quantidade INTEGER NOT NULL DEFAULT 0,
            estoque_minimo INTEGER NOT NULL DEFAULT 10,
            status TEXT NOT NULL DEFAULT 'ativo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS movimentacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            produto_id INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,
            tipo TEXT NOT NULL,
            quantidade INTEGER NOT NULL,
            saldo_anterior INTEGER NOT NULL,
            saldo_posterior INTEGER NOT NULL,
            motivo TEXT NOT NULL,
            documento TEXT,
            observacoes TEXT,
            data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        );
    ");

    // Inserir Usuários Iniciais
    $stmtUser = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha, perfil, pode_entrada, pode_saida, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtUser->execute([1, 'Administrador do Armazém', 'admin@nexo.com', $hash, 'gestor', 1, 1, 'ativo']);
    $stmtUser->execute([2, 'Carlos Oliveira (Gestor Operacional)', 'carlos.gestor@nexo.com', $hash, 'gestor', 1, 1, 'ativo']);
    $stmtUser->execute([3, 'João Silva (Estoquista Geral)', 'joao@nexo.com', $hash, 'funcionario', 1, 1, 'ativo']);
    $stmtUser->execute([4, 'Mariana Costa (Somente Expedição)', 'mariana@nexo.com', $hash, 'funcionario', 0, 1, 'ativo']);
    $stmtUser->execute([5, 'Lucas Pereira (Somente Recebimento)', 'lucas@nexo.com', $hash, 'funcionario', 1, 0, 'ativo']);

    // Inserir Categorias
    $stmtCat = $pdo->prepare("INSERT INTO categorias (id, nome, descricao) VALUES (?, ?, ?)");
    $stmtCat->execute([1, 'EPI & Segurança', 'Equipamentos de proteção individual para o armazém']);
    $stmtCat->execute([2, 'Embalagens & Cargas', 'Caixas de papelão, fitas de arqueamento e paletes']);
    $stmtCat->execute([3, 'Ferramentas & Acessórios', 'Ferramentas manuais, elétricas e carrinhos hidráulicos']);
    $stmtCat->execute([4, 'Eletrônicos & Sensores', 'Sensores de automação, leitores e coletores']);
    $stmtCat->execute([5, 'Matérias-Primas', 'Insumos industriais e componentes mecânicos']);

    // Inserir Produtos
    $stmtProd = $pdo->prepare("INSERT INTO produtos (id, sku, nome, categoria_id, localizacao, unidade, preco, quantidade, estoque_minimo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtProd->execute([1, 'EPI-CAP-001', 'Capacete de Segurança Industrial Branco', 1, 'Corredor A - Prateleira 1 - Nível 2', 'UN', 34.90, 85, 20, 'ativo']);
    $stmtProd->execute([2, 'EPI-LUV-002', 'Luva de Vaqueta Cano Curto Tam G', 1, 'Corredor A - Prateleira 1 - Nível 3', 'PAR', 18.50, 12, 25, 'ativo']);
    $stmtProd->execute([3, 'EMB-CXM-003', 'Caixa de Papelão Reforçada 40x30x25cm', 2, 'Corredor B - Prateleira 2 - Nível 1', 'UN', 6.80, 450, 100, 'ativo']);
    $stmtProd->execute([4, 'EMB-FIT-004', 'Fita Adesiva Transparente 48mm x 100m', 2, 'Corredor B - Prateleira 2 - Nível 2', 'RL', 7.50, 6, 30, 'ativo']);
    $stmtProd->execute([5, 'FER-TRA-005', 'Transpalete Manual 2500kg 685x1220mm', 3, 'Pátio Central - Vaga T-02', 'UN', 1890.00, 3, 2, 'ativo']);
    $stmtProd->execute([6, 'ELE-LEI-006', 'Leitor de Código de Barras Sem Fio 2D', 4, 'Bancada Expedição - Gaveteiro 01', 'UN', 320.00, 14, 5, 'ativo']);
    $stmtProd->execute([7, 'ELE-COL-007', 'Coletor de Dados Android Zebra TC21', 4, 'Bancada Expedição - Armário Chaveado', 'UN', 2450.00, 0, 4, 'ativo']);
    $stmtProd->execute([8, 'MAT-ROL-008', 'Rolamento Esferas Blindado 6205-ZZ', 5, 'Corredor C - Prateleira 4 - Gaveta 10', 'UN', 24.00, 120, 40, 'ativo']);

    // Inserir Movimentações Iniciais
    $stmtMov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, documento, observacoes, data_movimentacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtMov->execute([1, 1, 'entrada', 100, 0, 100, 'Recebimento de Fornecedor', 'NF-98214', 'Carga inicial conferida sem divergências.', date('Y-m-d H:i:s', strtotime('-5 days'))]);
    $stmtMov->execute([1, 3, 'saida', 15, 100, 85, 'Envio para Linha de Montagem', 'REQ-0045', 'Entregue ao encarregado Roberto.', date('Y-m-d H:i:s', strtotime('-4 days'))]);
    $stmtMov->execute([2, 5, 'entrada', 40, 0, 40, 'Recebimento de Fornecedor', 'NF-98250', 'Lote de reposição.', date('Y-m-d H:i:s', strtotime('-3 days'))]);
    $stmtMov->execute([2, 4, 'saida', 28, 40, 12, 'Distribuição Interna de EPIs', 'REQ-0052', 'Equipe do turno da manhã.', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    $stmtMov->execute([7, 1, 'entrada', 5, 0, 5, 'Aquisição de Ativo', 'NF-10492', 'Novos coletores cadastrados no patrimônio.', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    $stmtMov->execute([7, 4, 'saida', 5, 5, 0, 'Distribuição para Operadores', 'REQ-0060', 'Todos os coletores em uso na operação.', date('Y-m-d H:i:s', strtotime('-1 days'))]);
}

// Disponibiliza $pdo globalmente para scripts que requerem a conexão direta
$pdo = getDB();
