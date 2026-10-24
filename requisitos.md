# Nexo

## OBJETIVOS 
Desenvolva um aplicativo chamado NEXO WMS, um sistema de gerenciamento de armazém (Warehouse Management System) destinado a organizar e controlar as operações de estoque.

O aplicativo deve possuir dois tipos de usuários: Funcionário e Gestor, cada um com permissões específicas.

Funcionário:

- Acessar o sistema por meio de login.
- Consultar produtos e quantidades em estoque.
- Registrar entradas e saídas de mercadorias, conforme suas permissões.
- Consultar informações sobre as movimentações realizadas.

Gestor:

- Acessar um painel administrativo.
- Cadastrar, editar e desativar usuários.
- Gerenciar permissões de acesso.
- Cadastrar e gerenciar produtos.
- Acompanhar entradas, saídas e níveis de estoque.
- Visualizar relatórios de movimentação e indicadores de estoque.

Requisitos gerais:

- Criar uma interface moderna, intuitiva e responsiva.
- Organizar as funcionalidades de maneira simples e acessível.
- Implementar controle de acesso por perfil de usuário.
- Garantir que as informações de estoque sejam atualizadas conforme as operações realizadas.
- Exibir mensagens claras de sucesso e erro.
- Manter o histórico das movimentações de mercadorias.
- Utilizar uma estrutura organizada, que facilite futuras melhorias no aplicativo.

O resultado deve ser um sistema funcional, com navegação intuitiva e recursos adequados às necessidades de um armazém.
### STACK TECNÓLOGICO
- Backend: PHP estruturado em sessões nativas
- Banco de dados: MySQL (PDO para segurança)
- Frontend: HTML5, PHP, CSS, Tailwind CSS

#### Regras de negocio (CORE)

1. Funcionário

- O funcionário deve realizar login para acessar o sistema.
- O funcionário pode consultar o estoque.
- O funcionário pode registrar entradas e saídas de mercadorias, conforme suas permissões.
- O funcionário não pode gerenciar as permissões de outros usuários.

2. Gestor

- O gestor deve realizar login para acessar o sistema.
- O gestor pode cadastrar, editar e desativar usuários.
- O gestor pode definir as permissões dos funcionários.
- O gestor pode consultar o estoque e os relatórios de movimentação.
- O gestor pode acompanhar as entradas e saídas de mercadorias.

##### Regras globais
- Use sempre o mesmo PDO para conexão e quieres no MySQL para evitar SQL Injections
- Mantenha o código limpo e comente apenas logicas complexas.
- Separe os arquivos  de forma logica: um arquivo para conexão com a base (bd.php) e scrips de backend isolados em views em HTML5/PHP
- Estilize as telas em tailwind de forma responsiva priorizando o MobileFrist.
- Retorne sempre as mensagens de erros de forma claras na interface para o usuário (TOAST)
- sempre trate as mensagens de caixa de mensagens de caixa de mensagens nativas de mensagens nativas do navegador em um MODAL
