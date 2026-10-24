# NEXO WMS - Sistema de Gerenciamento de Armazém

O **NEXO WMS** é um sistema completo, moderno e responsivo para gerenciamento de armazém (*Warehouse Management System*), desenvolvido rigorosamente de acordo com os requisitos especificados em [requisitos.md](requisitos.md).

---

## 🚀 Tecnologias Utilizadas

- **Backend:** PHP 8+ estruturado em sessões nativas (`session_start()`).
- **Banco de Dados:** MySQL com conexão exclusiva via **PDO** e *Prepared Statements* contra SQL Injection. (Inclui contingência automática para SQLite para testes instantâneos).
- **Frontend:** HTML5, CSS3, Tailwind CSS (Mobile-First), FontAwesome 6 e Google Fonts (Plus Jakarta Sans).
- **Notificações:** Sistema de **Toast flutuante** elegante integrado às sessões PHP.
- **Diálogos:** Substituição total de `alert()` e `confirm()` nativos do navegador por **Modais interativos estilizados em Tailwind**.

---

## 👥 Perfis de Usuários e Regras de Negócio

### 1. Funcionário Operacional
- Realiza login no sistema.
- Consulta catálogo de produtos, saldos em estoque e endereçamento físico (corredor, prateleira).
- Registra **Entradas** de mercadorias (apenas se concedido pelo Gestor).
- Registra **Saídas / Expedição** de mercadorias (apenas se concedido pelo Gestor).
- Consulta histórico e auditoria completa de movimentações realizadas.
- Não possui acesso à gestão de usuários nem exclusão de materiais.

### 2. Gestor do Armazém
- Acesso ao **Painel Executivo (Dashboard)** com indicadores em tempo real (Total de Itens, Produtos Cadastrados, Alertas de Estoque Baixo, Balanço de Entradas e Saídas).
- **Gestão de Produtos:** Cadastrar, editar, consultar e desativar produtos com definição de SKU, estoque mínimo e localização.
- **Gestão de Usuários & Permissões:** Cadastrar novos usuários, editar dados, ativar/desativar e definir granularmente permissões de **Entrada** e **Saída** para cada funcionário.
- **Relatórios Gerenciais & Indicadores:** Filtros por período, tipo de operação, categoria, visualização de top produtos mais movimentados, impressão de relatório e **Exportação para CSV**.
- **Controle Transacional Atômico:** Todas as movimentações utilizam transações PDO (`beginTransaction` / `commit` / `rollBack`), garantindo que saídas nunca ultrapassem o saldo disponível.

---

## 🔑 Credenciais de Acesso (Demonstração)

A tela de login conta com botões de **1-Clique para Preenchimento Automático**:

| Perfil | E-mail | Senha | Permissões |
|---|---|---|---|
| **Gestor Geral** | `admin@nexo.com` | `admin123` | Acesso Total (Administrativo) |
| **Gestor Operacional** | `carlos.gestor@nexo.com` | `admin123` | Acesso Total (Administrativo) |
| **Funcionário Geral** | `joao@nexo.com` | `admin123` | Entrada & Saída Liberadas |
| **Mariana (Expedição)** | `mariana@nexo.com` | `admin123` | **Somente Saída** (Entrada Bloqueada) |
| **Lucas (Recebimento)** | `lucas@nexo.com` | `admin123` | **Somente Entrada** (Saída Bloqueada) |

---

## 📂 Estrutura de Arquivos

```text
SOL/
├── bd.php                       # Conexão centralizada PDO com proteção e fallback
├── schema.sql                   # Estrutura completa do banco MySQL e carga de dados
├── run_tests.php                # Bateria automatizada com 21 testes unitários e de integração
├── index.php                    # Redirecionador inicial (Login ou Dashboard)
├── login.php                    # Tela de autenticação responsiva com botões rápidos de demo
├── logout.php                   # Encerramento seguro de sessão
├── dashboard.php                # Painel principal de controle e KPIs
├── estoque.php                  # Consulta de estoque e inventário em tempo real
├── produtos.php                 # Catálogo e listagem de produtos
├── produto_form.php             # Cadastro e edição de produtos (Gestor)
├── movimentacao.php             # Registro de Entradas e Saídas com validação de permissões
├── historico.php                # Histórico e auditoria de movimentações com filtros
├── usuarios.php                 # Gestão de usuários e permissões operacionais (Gestor)
├── usuario_form.php             # Cadastro e edição de colaboradores e permissões (Gestor)
├── relatorios.php               # Relatórios analíticos com indicadores e exportação CSV (Gestor)
├── includes/
│   ├── auth.php                 # Middleware de autenticação, sessão, CSRF e Toasts
│   ├── header.php               # Cabeçalho HTML, Tailwind CDN, ícones e Toasts
│   ├── sidebar.php              # Sidebar fixa no desktop e gaveta drawer no mobile
│   ├── footer.php               # Scripts globais, auto-dismiss de toasts e listeners
│   └── modal.php                # Componente reutilizável de modal Tailwind
└── actions/
    ├── auth_action.php          # Processamento de login seguro
    ├── produto_action.php       # Processamento de CRUD de produtos
    ├── movimentacao_action.php  # Processamento transacional de entradas e saídas
    └── usuario_action.php       # Processamento de CRUD e permissões de usuários
```

---

## ⚙️ Como Executar o Projeto

### Opção 1: Servidor Embutido do PHP (Recomendado e Imediato)
No terminal da pasta do projeto, execute:
```bash
php -S localhost:8000
```
Em seguida, abra o navegador e acesse:
[http://localhost:8000](http://localhost:8000)

### Opção 2: Servidor Apache / MySQL (XAMPP / WAMP / Laragon)
1. Coloque a pasta do projeto dentro de `htdocs` ou `www`.
2. No phpMyAdmin ou no terminal do MySQL, importe o arquivo `schema.sql`.
3. Caso queira configurar usuário e senha personalizados para o MySQL, altere as constantes no início de `bd.php`:
   - `DB_HOST`: padrão `127.0.0.1`
   - `DB_NAME`: padrão `nexo_wms`
   - `DB_USER`: padrão `root`
   - `DB_PASS`: padrão vazio `""`
4. Acesse no navegador em `http://localhost/SOL/`.

---

## 🧪 Bateria de Testes Automatizados

O sistema conta com um script de validação de todas as regras de negócio:
```bash
php run_tests.php
```
O teste valida automaticamente:
1. Conexão PDO e integridade das 4 tabelas relacionais.
2. Criptografia BCRYPT e autenticação de usuários.
3. RBAC (Controle de acesso por perfil e bloqueios de permissão).
4. Transação atômica de Entrada com cálculo de saldo.
5. Transação atômica de Saída com dedução de estoque.
6. Bloqueio estrito de Saída com saldo insuficiente (proteção contra estoque negativo).
7. Rastreabilidade e gravação de histórico de auditoria.
