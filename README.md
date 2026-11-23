# 🍕 Cria Fatia

**Sistema web de pedidos de pizza online com montagem personalizada**, desenvolvido para a **Bella Roza Pizzaria** como Projeto Integrador do 5º semestre do curso de **Tecnologia em Sistemas para Internet – Senac**.

O Cria Fatia leva o pedido do cliente até a cozinha sem ruído: o cliente monta a pizza do jeito que quer, o atendimento confere e encaminha, a cozinha produz a partir de um ticket digital claro e o administrador acompanha tudo — cardápio, pedidos, caixa e indicadores — em um só lugar.

🔗 **Acesse a aplicação online:** **<https://cria-fatia-vercel.vercel.app/>**

| Área | Link |
|---|---|
| Página inicial e cardápio | <https://cria-fatia-vercel.vercel.app/> |
| Montar pizza | <https://cria-fatia-vercel.vercel.app/cliente/montar.php> |
| Acompanhar pedido | <https://cria-fatia-vercel.vercel.app/cliente/pedido.php> |
| Login (equipe da pizzaria) | <https://cria-fatia-vercel.vercel.app/login.php> |

---

## ✨ Funcionalidades

### 👤 Cliente (área pública)
- **Montador de pizza** com prévia visual e preço atualizado em tempo real: tamanho, tipo de massa, ingredientes, borda e molhos.
- **Checkout** com entrega ou retirada, e pagamento via **Pix (com QR Code)**, **cartão** ou **dinheiro com cálculo de troco**.
- **Acompanhamento do pedido** por código (ex.: `CF-A1B2C3`), com o status atualizado conforme o pedido avança.
- Páginas de **Termos de Uso** e **Política de Privacidade**.

### 🧾 Atendimento
- Painel de pedidos recebidos com **filtros por status** e **busca rápida**.
- Atualização de status e envio do pedido para produção.
- Contato com o cliente via **WhatsApp**.
- **Impressão de comanda** em formato de cupom térmico (80 mm).
- Abertura e fechamento de **caixa**.

### 👨‍🍳 Cozinha
- Painel **KDS (Kitchen Display System)** com tickets digitais e etiquetas coloridas por item.
- **Atualização automática** e **alerta sonoro** a cada novo pedido.
- Marcação de pedido em preparo e pronto.

### 🛠️ Administrador
- **Dashboard** com indicadores de faturamento e gráficos (Chart.js), incluindo os ingredientes mais pedidos.
- **CRUDs completos** de tamanhos, tipos de massa, ingredientes, bordas, molhos e sabores.
- **Histórico de pedidos** e **controle de caixa** (abertura, fechamento, entradas, saídas e vendas).
- **Auditoria**: registro das alterações feitas no sistema e do histórico de status de cada pedido.

---

## 🔄 Fluxo do pedido

```
 Cliente monta a pizza ─► Checkout ─► Atendimento confere ─► Cozinha prepara ─► Pronto ─► Entregue / Finalizado
                                                  │
                                                  └─► Cancelado
```

| Status | Significado |
|---|---|
| `pendente` | Pedido recebido, aguardando o atendimento |
| `em_producao` | Enviado para a cozinha |
| `pronto` | Preparado, aguardando entrega ou retirada |
| `entregue` / `finalizado` | Pedido concluído (a venda entra no caixa) |
| `cancelado` | Pedido cancelado |

---

## 🛠️ Tecnologias

| Camada | Tecnologias |
|---|---|
| **Backend** | PHP 8.x com PDO e *prepared statements* |
| **Banco de dados** | MySQL 8 / MariaDB 10.x |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons |
| **Gráficos** | Chart.js |
| **Deploy** | Vercel, Docker, FrankenPHP (Caddy) |
| **Ambiente de desenvolvimento** | WAMP (Apache + MySQL + PHP) |

---

## 📂 Estrutura do projeto

```text
Cria-Fatia/
├── Dockerfile                  # Imagem PHP 8.4 + FrankenPHP usada no deploy
├── Caddyfile                   # Servidor web (bloqueia config/, database/, includes/ e .sql)
├── vercel-entrypoint.sh        # Inicializa o banco e sobe o servidor
├── cria-fatia.sql              # Dump completo do banco (estrutura + dados iniciais)
└── cria-fatia/                 # Aplicação web
    ├── index.php               # Página inicial, cardápio e acompanhamento de pedido
    ├── login.php / logout.php  # Autenticação
    ├── termos-de-uso.php
    ├── politica-privacidade.php
    ├── admin/                  # Área do administrador
    │   ├── dashboard.php       # Indicadores e gráficos
    │   ├── pedidos.php         # Histórico de pedidos
    │   ├── caixa.php           # Controle de caixa (admin e atendimento)
    │   ├── tamanho.php         # CRUD de tamanhos
    │   ├── massas.php          # CRUD de tipos de massa
    │   ├── ingredientes.php    # CRUD de ingredientes
    │   ├── bordas.php          # CRUD de bordas
    │   ├── molhos.php          # CRUD de molhos
    │   └── sabores.php         # CRUD de sabores
    ├── atendimento/
    │   ├── index.php           # Painel de pedidos do atendimento
    │   └── imprimir.php        # Comanda para impressora térmica
    ├── cozinha/
    │   └── index.php           # Painel KDS da cozinha
    ├── cliente/
    │   ├── montar.php          # Montador de pizza
    │   ├── finalizar.php       # Checkout e validação no servidor
    │   └── pedido.php          # Recibo e acompanhamento
    ├── config/
    │   ├── config.php          # Sessão e URL base
    │   ├── conexao.php         # Conexão PDO (lê variáveis de ambiente)
    │   └── database_session_handler.php  # Sessões salvas no banco
    ├── includes/               # Header, footer e funções (CSRF, perfis, auditoria)
    ├── assets/                 # CSS e JavaScript (montador.js, app.js)
    └── database/               # Scripts SQL, migrações e initialize.php
```

---

## 🚀 Como executar localmente

> Não quer instalar nada? Use a versão online: <https://cria-fatia-vercel.vercel.app/>

### Pré-requisitos
- Servidor local com Apache, PHP e MySQL — por exemplo **WAMP**, XAMPP ou Laragon.
- PHP 8.0 ou superior com a extensão `pdo_mysql` ativa.
- MySQL 5.7+ ou MariaDB 10.x.

### Passo a passo

1. **Clone o repositório**
   ```bash
   git clone https://github.com/CauaBrandao/Cria-Fatia.git
   ```

2. **Copie a pasta da aplicação** `cria-fatia/` para a pasta pública do servidor:
   - WAMP: `C:\wamp64\www\cria-fatia`
   - XAMPP: `C:\xampp\htdocs\cria-fatia`

3. **Importe o banco de dados.** No phpMyAdmin (ou outro cliente MySQL), execute o arquivo **`cria-fatia.sql`** que está na raiz do repositório. Ele cria o banco `cria_fatia` com todas as tabelas e os dados iniciais.
   ```bash
   mysql -u root -p < cria-fatia.sql
   ```

4. **Confira a conexão.** Sem nenhuma configuração extra, o sistema se conecta com os padrões do WAMP/XAMPP: host `localhost`, porta `3306`, usuário `root`, senha vazia e banco `cria_fatia`. Se o seu MySQL for diferente, defina as variáveis de ambiente `DB_HOST`, `DB_USER`, `DB_PASS` e `DB_NAME` (veja a seção **Deploy na Vercel**, abaixo).

5. **Informe a URL da aplicação.** Por padrão, o sistema considera que está na raiz do domínio (como na Vercel). Rodando em uma subpasta do WAMP/XAMPP, adicione esta linha ao `cria-fatia/.htaccess`:
   ```apache
   SetEnv APP_URL http://localhost/cria-fatia/
   ```

6. **Acesse no navegador:** <http://localhost/cria-fatia/>

> 💡 **Alternativa rápida, sem WAMP:** com o PHP instalado, rode `php -S localhost:8000 -t cria-fatia` na raiz do repositório e acesse <http://localhost:8000>. Nesse caso não é preciso configurar o `APP_URL`.

---

## ☁️ Deploy na Vercel

A aplicação está publicada em **<https://cria-fatia-vercel.vercel.app/>**.

O deploy usa o `Dockerfile` da raiz: uma imagem **PHP 8.4 com FrankenPHP**, que serve a pasta `cria-fatia/` e bloqueia o acesso público a `config/`, `database/`, `includes/` e aos arquivos `.sql` e `.md`. O banco de dados fica em um serviço MySQL externo.

**Como funciona ao iniciar:**
1. O `vercel-entrypoint.sh` executa `database/initialize.php`.
2. Se o banco ainda não tiver as tabelas, ele importa automaticamente o `database/cria_fatia.sql` (estrutura e dados iniciais).
3. O servidor FrankenPHP é iniciado.

As **sessões de login são guardadas no banco** (tabela `php_sessions`), para funcionarem em ambiente serverless, onde não há disco persistente.

**Variáveis de ambiente aceitas:**

| Variável | Uso |
|---|---|
| `DATABASE_URL` | Conexão completa, ex.: `mysql://usuario:senha@host:3306/cria_fatia` |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Alternativa à `DATABASE_URL`, campo a campo |
| `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD` | Formato usado por provedores como o Railway |
| `TIDB_HOST`, `TIDB_PORT`, `TIDB_USER`, `TIDB_PASSWORD`, `TIDB_DATABASE` | Para TiDB Cloud (conexão com SSL) |
| `APP_URL` | URL pública da aplicação (opcional; detectada automaticamente) |

**Rodar a mesma imagem localmente com Docker:**
```bash
docker build -t cria-fatia .
docker run -p 8080:80 -e DATABASE_URL="mysql://usuario:senha@host:3306/cria_fatia" cria-fatia
```
Depois, acesse <http://localhost:8080>.

---

## 🔑 Usuários de teste

O acesso às áreas internas é feito em `/login.php` — online: <https://cria-fatia-vercel.vercel.app/login.php>. Todos os usuários iniciais usam a senha **`admin123`**.

| Perfil | E-mail | Acesso |
|---|---|---|
| **Administrador** | `admin@briafatia.com` | Todas as áreas |
| **Atendimento** | `atendimento@briafatia.com` | Painel de atendimento, comandas e caixa |
| **Cozinha** | `cozinha@briafatia.com` | Painel KDS e comandas |

> ⚠️ Essas credenciais são apenas para demonstração. Troque as senhas antes de usar o sistema em um ambiente real.

---

## ⚙️ Regras de negócio

- **Composição do preço:**
  `preço do tamanho + adicional da massa + ingredientes escolhidos + borda + molhos + taxa de entrega`
- **Taxa de entrega** de R$ 5,00, aplicada apenas quando o cliente escolhe *entrega* (retirada é gratuita).
- É obrigatório escolher **pelo menos um ingrediente**.
- O **valor final é sempre recalculado no servidor** (`cliente/finalizar.php`) a partir do banco — o preço enviado pelo navegador não é considerado.
- Itens marcados como indisponíveis no painel administrativo não podem ser pedidos.
- Só pode existir **um caixa aberto por vez**; pedidos concluídos geram a movimentação de venda no caixa.

---

## 🔒 Segurança

- Senhas armazenadas com **bcrypt** (`password_hash` / `password_verify`).
- **Controle de acesso por perfil** (`admin`, `atendimento`, `cozinha`) em todas as páginas internas.
- **Token CSRF** nos formulários de montagem, checkout e administração.
- Consultas com **prepared statements** (proteção contra SQL Injection).
- Saída escapada com `htmlspecialchars` (proteção contra XSS).
- Acompanhamento de pedido por **código + token de acesso** aleatórios.
- Listagem de diretórios desativada via `.htaccess`.

---

## 🗄️ Banco de dados

Principais tabelas do banco `cria_fatia`:

| Grupo | Tabelas |
|---|---|
| **Cardápio** | `tamanhos`, `tipos_massas`, `ingredientes`, `bordas`, `molhos`, `sabores`, `sabor_ingredientes` |
| **Pedidos** | `pedidos`, `pedido_itens`, `pedido_item_customizacoes`, `pedido_item_molhos` |
| **Financeiro** | `caixas`, `caixa_movimentacoes` |
| **Usuários e auditoria** | `usuarios`, `historico_alteracoes`, `historico_status_pedidos` |

A pasta `cria-fatia/database/` guarda os scripts usados ao longo do desenvolvimento (versões anteriores do banco e migrações). Para uma instalação nova, basta o `cria-fatia.sql` da raiz.

---

## 👥 Equipe

Projeto Integrador – 5º semestre · Tecnologia em Sistemas para Internet · **Senac** · 2026

- **Cauã Brandão** 
- **Fernando Souza**
- **Luiz Felipe Rodrigues**
- **Ricardo Godoy**

---

<p align="center">Feito com 🍕 para a <strong>Bella Roza Pizzaria</strong></p>
