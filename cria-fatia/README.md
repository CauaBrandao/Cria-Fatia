# Cria Fatia

Aplicação PHP para montagem de pizzas, checkout, acompanhamento de pedidos e operação interna da Bella Roza Pizzaria.

## Recursos

- Montagem por tamanho, massa, até 8 ingredientes, borda e molhos.
- Preço recalculado no servidor; o valor enviado pelo navegador não é confiável.
- Checkout com entrega ou retirada e acompanhamento por código.
- Áreas separadas para administração, atendimento e cozinha.
- Proteções de sessão, CSRF, consultas PDO preparadas e controle de acesso por perfil.
- Migrações incrementais executadas automaticamente na inicialização do contêiner.

## Requisitos

- PHP 8.2 ou superior com `pdo_mysql`.
- MySQL 8, MariaDB compatível ou TiDB.
- Servidor web apontando para esta pasta.

## Configuração

Configure as variáveis de ambiente conforme o provedor:

```text
APP_URL=https://seu-dominio.example/
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cria_fatia
DB_USER=usuario
DB_PASS=senha
ADMIN_INITIAL_EMAIL=administrador@example.com
ADMIN_INITIAL_PASSWORD=uma-senha-forte-com-12-ou-mais-caracteres
```

Em TiDB, use `TIDB_HOST`, `TIDB_PORT`, `TIDB_DATABASE`, `TIDB_USER` e `TIDB_PASSWORD`. Também é aceito `DATABASE_URL`.

As variáveis `ADMIN_INITIAL_*` só são usadas quando a tabela de usuários está vazia. Nunca versione credenciais reais; mantenha dados sensíveis nas variáveis de ambiente do serviço de hospedagem.

## Banco de dados

O arquivo canônico é `database/cria_fatia.sql`. Em contêiner, `database/initialize.php` cria o esquema quando necessário e aplica, uma única vez e em ordem, os arquivos de `database/migrations/`.

## Estrutura

```text
admin/          gestão de catálogo, pedidos e caixa
atendimento/    operação e impressão de pedidos
cliente/        montagem, checkout e acompanhamento
cozinha/        painel de produção
config/         conexão, sessão e configuração
database/       esquema, inicializador e migrações
includes/       cabeçalho, rodapé e funções compartilhadas
assets/         CSS e JavaScript global
```

## Segurança operacional

- Troque ou remova usuários iniciais antes de usar o sistema com dados reais.
- Mantenha `display_errors` desativado em produção.
- Use HTTPS e restrinja o acesso ao banco por rede e usuário.
- Faça backup antes de aplicar migrações em uma instalação existente.

## Validação

Antes de publicar, execute lint em todos os arquivos PHP:

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Depois, valide os fluxos de login, montagem, checkout, acompanhamento, atendimento, cozinha e administração em um banco de teste.
