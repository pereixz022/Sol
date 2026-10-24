-- ===================================================
-- NEXO WMS - Sistema de Gerenciamento de Armazém
-- Banco de Dados MySQL
-- ===================================================

CREATE DATABASE IF NOT EXISTS `nexo_wms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nexo_wms`;

-- ---------------------------------------------------
-- 1. Tabela: usuarios
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('gestor', 'funcionario') NOT NULL DEFAULT 'funcionario',
  `pode_entrada` TINYINT(1) NOT NULL DEFAULT 1,
  `pode_saida` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. Tabela: categorias
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL UNIQUE,
  `descricao` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. Tabela: produtos
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `nome` VARCHAR(150) NOT NULL,
  `categoria_id` INT NULL,
  `localizacao` VARCHAR(100) NOT NULL,
  `unidade` VARCHAR(20) NOT NULL DEFAULT 'UN',
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantidade` INT NOT NULL DEFAULT 0,
  `estoque_minimo` INT NOT NULL DEFAULT 10,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. Tabela: movimentacoes
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `movimentacoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('entrada', 'saida') NOT NULL,
  `quantidade` INT NOT NULL,
  `saldo_anterior` INT NOT NULL,
  `saldo_posterior` INT NOT NULL,
  `motivo` VARCHAR(150) NOT NULL,
  `documento` VARCHAR(100) NULL,
  `observacoes` TEXT NULL,
  `data_movimentacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Inserção de Dados Iniciais de Teste e Demonstração
-- Senhas criptografadas usando password_hash BCRYPT
-- 'admin123' -> $2y$10$wO37x4dJ8qf1bIe1b5f1m.8E01y3sO1r0uLp8iWc7uPzFz.Z4fX9a (serão criados dinamicamente ou com hash válido)
-- ---------------------------------------------------

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `pode_entrada`, `pode_saida`, `status`) VALUES
(1, 'Administrador do Armazém', 'admin@nexo.com', '$2y$10$qRBDkVY98xZ6cwhu6Y/0mO6qV/EwhV4kO3vIeQ7hI.tYmN9Cg168C', 'gestor', 1, 1, 'ativo'),
(2, 'Carlos Oliveira (Gestor Operacional)', 'carlos.gestor@nexo.com', '$2y$10$qRBDkVY98xZ6cwhu6Y/0mO6qV/EwhV4kO3vIeQ7hI.tYmN9Cg168C', 'gestor', 1, 1, 'ativo'),
(3, 'João Silva (Estoquista Geral)', 'joao@nexo.com', '$2y$10$qRBDkVY98xZ6cwhu6Y/0mO6qV/EwhV4kO3vIeQ7hI.tYmN9Cg168C', 'funcionario', 1, 1, 'ativo'),
(4, 'Mariana Costa (Somente Expedição)', 'mariana@nexo.com', '$2y$10$qRBDkVY98xZ6cwhu6Y/0mO6qV/EwhV4kO3vIeQ7hI.tYmN9Cg168C', 'funcionario', 0, 1, 'ativo'),
(5, 'Lucas Pereira (Somente Recebimento)', 'lucas@nexo.com', '$2y$10$qRBDkVY98xZ6cwhu6Y/0mO6qV/EwhV4kO3vIeQ7hI.tYmN9Cg168C', 'funcionario', 1, 0, 'ativo');
-- Nota: A senha padrão de todos os usuários de teste acima é: admin123

INSERT INTO `categorias` (`id`, `nome`, `descricao`) VALUES
(1, 'EPI & Segurança', 'Equipamentos de proteção individual para o armazém'),
(2, 'Embalagens & Cargas', 'Caixas de papelão, fitas de arqueamento e paletes'),
(3, 'Ferramentas & Acessórios', 'Ferramentas manuais, elétricas e carrinhos hidráulicos'),
(4, 'Eletrônicos & Sensores', 'Sensores de automação, leitores de código de barras e coletores'),
(5, 'Matérias-Primas', 'Insumos industriais e componentes mecânicos');

INSERT INTO `produtos` (`id`, `sku`, `nome`, `categoria_id`, `localizacao`, `unidade`, `preco`, `quantidade`, `estoque_minimo`, `status`) VALUES
(1, 'EPI-CAP-001', 'Capacete de Segurança Industrial Branco', 1, 'Corredor A - Prateleira 1 - Nível 2', 'UN', 34.90, 85, 20, 'ativo'),
(2, 'EPI-LUV-002', 'Luva de Vaqueta Cano Curto Tam G', 1, 'Corredor A - Prateleira 1 - Nível 3', 'PAR', 18.50, 12, 25, 'ativo'),
(3, 'EMB-CXM-003', 'Caixa de Papelão Reforçada 40x30x25cm', 2, 'Corredor B - Prateleira 2 - Nível 1', 'UN', 6.80, 450, 100, 'ativo'),
(4, 'EMB-FIT-004', 'Fita Adesiva Transparente 48mm x 100m', 2, 'Corredor B - Prateleira 2 - Nível 2', 'RL', 7.50, 6, 30, 'ativo'),
(5, 'FER-TRA-005', 'Transpalete Manual 2500kg 685x1220mm', 3, 'Pátio Central - Vaga T-02', 'UN', 1890.00, 3, 2, 'ativo'),
(6, 'ELE-LEI-006', 'Leitor de Código de Barras Sem Fio 2D', 4, 'Bancada Expedição - Gaveteiro 01', 'UN', 320.00, 14, 5, 'ativo'),
(7, 'ELE-COL-007', 'Coletor de Dados Android Zebra TC21', 4, 'Bancada Expedição - Armário Chaveado', 'UN', 2450.00, 0, 4, 'ativo'),
(8, 'MAT-ROL-008', 'Rolamento Esferas Blindado 6205-ZZ', 5, 'Corredor C - Prateleira 4 - Gaveta 10', 'UN', 24.00, 120, 40, 'ativo');

INSERT INTO `movimentacoes` (`id`, `produto_id`, `usuario_id`, `tipo`, `quantidade`, `saldo_anterior`, `saldo_posterior`, `motivo`, `documento`, `observacoes`, `data_movimentacao`) VALUES
(1, 1, 1, 'entrada', 100, 0, 100, 'Recebimento de Fornecedor', 'NF-98214', 'Carga inicial conferida sem divergências.', NOW() - INTERVAL 5 DAY),
(2, 1, 3, 'saida', 15, 100, 85, 'Envio para Linha de Montagem', 'REQ-0045', 'Entregue ao encarregado Roberto.', NOW() - INTERVAL 4 DAY),
(3, 2, 5, 'entrada', 40, 0, 40, 'Recebimento de Fornecedor', 'NF-98250', 'Lote de reposição.', NOW() - INTERVAL 3 DAY),
(4, 2, 4, 'saida', 28, 40, 12, 'Distribuição Interna de EPIs', 'REQ-0052', 'Equipe do turno da manhã.', NOW() - INTERVAL 2 DAY),
(5, 7, 1, 'entrada', 5, 0, 5, 'Aquisição de Ativo', 'NF-10492', 'Novos coletores cadastrados no patrimônio.', NOW() - INTERVAL 2 DAY),
(6, 7, 4, 'saida', 5, 5, 0, 'Distribuição para Operadores', 'REQ-0060', 'Todos os coletores em uso na operação.', NOW() - INTERVAL 1 DAY);
