# Cria Fatia - Sistema de Montagem de Pizza Visual e Dinâmico

## 🍕 Apresentação
O **Cria Fatia** é uma aplicação web completa desenvolvida como Produto Mínimo Viável (MVP) e expandida para o Projeto Integrador (Senac 2026), voltado à **Bella Roza Pizzaria**.

O sistema automatiza e padroniza a montagem personalizada de pizzas, eliminando ambiguidades e erros entre a escolha do cliente, a conferência do atendimento e o preparo na cozinha.

---

## 🛠️ Tecnologias Utilizadas
- **Backend:** PHP 8.x (Arquitetura MVC/Modular simples com PDO e Prepared Statements).
- **Banco de Dados:** MySQL / MariaDB (Relacional com Chaves Estrangeiras e Índices).
- **Frontend:** HTML5, CSS3 Customizado, JavaScript ES6+, Bootstrap 5.3 e Bootstrap Icons.
- **Segurança:** Hashing de Senhas com `password_hash()` / `password_verify()`, Validação Backend de Preços, Proteção CSRF por Token e Sanitização contra XSS.

---

## 📂 Estrutura de Diretórios
```text
cria-fatia/
├── .htaccess                          # Regras de segurança de diretório
├── index.php                          # Landing page inicial
├── login.php                          # Autenticação segura de usuários
├── logout.php                         # Destruição segura de sessão
├── README.md                          # Documentação completa
├── admin/                             # Módulo de Gestão (Perfil Admin)
│   ├── bordas.php                     # CRUD de bordas recheadas
│   ├── dashboard.php                  # Indicadores financeiros e métricas
│   ├── index.php                      # Redirecionamento seguro
│   ├── ingredientes.php               # CRUD de ingredientes extras
│   ├── pedidos.php                    # Histórico e auditoria de pedidos
│   └── sabores.php                    # CRUD de sabores do cardápio
├── assets/                            # Recursos estáticos
│   ├── css/
│   │   └── style.css                  # Estilização responsiva e preview da pizza
│   └── js/
│       ├── app.js                     # Scripts globais
│       └── montador.js                # Lógica de cálculo visual no navegador
├── atendimento/                       # Módulo do Atendimento (Perfil Admin / Atendimento)
│   └── index.php                      # Gestão de fluxo de pedidos recebidos
├── cliente/                           # Módulo Público / Cliente
│   ├── finalizar.php                  # Form de Checkout e validação no servidor
│   ├── montar.php                     # Montador visual dinâmico
│   └── pedido.php                     # Recibo e consulta do pedido
├── config/                            # Configurações de Conexão
│   ├── conexao.php                    # Conexão PDO segura
│   └── config.php                     # Constantes globais e sessões
├── cozinha/                           # Módulo da Cozinha (Perfil Admin / Cozinha)
│   └── index.php                      # Painel KDS (Ticket Digital com etiquetas coloridas)
├── database/                          # Scripts SQL e Migrações
│   ├── atualizacao_etapa_2.sql        # Índices e otimizações
│   ├── atualizacao_etapa_3.sql        # Suporte a futuras expansões
│   ├── atualizacao_montagem_por_ingredientes.sql # Migração para montagem por ingredientes
│   └── cria_fatia.sql                 # DDL completo e carga inicial
└── includes/                          # Componentes reutilizáveis
    ├── footer.php                     # Rodapé padronizado
    ├── funcoes.php                    # Sanitização, CSRF e controle de perfil
    └── header.php                     # Topbar responsivo e mensagens
```

---

## ⚡ Instalação e Configuração

### 1. Requisitos
- Servidor Web (Apache / Nginx).
- PHP 7.4+ ou PHP 8.x com extensões `pdo_mysql` ativadas.
- MySQL 5.7+ ou MariaDB 10.x.

### 2. Configuração do Banco de Dados
1. Abra seu gerenciador de banco (phpMyAdmin, DBeaver ou CLI).
2. Execute o arquivo SQL localizado em `database/cria_fatia.sql`.
3. Se o banco já existia antes desta alteração, execute também `database/atualizacao_montagem_por_ingredientes.sql` para permitir que os campos antigos de sabor fiquem vazios.
4. A montagem agora é feita selecionando ingredientes; o preço considera tamanho + ingredientes + borda + molhos.

### 3. Configuração da Conexão PHP
Abra o arquivo `config/config.php` e ajuste os parâmetros conforme seu ambiente local:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cria_fatia');
define('BASE_URL', 'http://localhost/cria-fatia/');
```

---

## 🔑 Usuários e Perfis de Acesso

O sistema possui autenticação baseada em perfis (`admin`, `atendimento`, `cozinha`). Todas as senhas iniciais foram configuradas para `admin123`.

| Perfil | E-mail | Senha | Permissões |
|---|---|---|---|
| **Administrador** | `admin@briafatia.com` | `admin123` | Acesso total: Dashboard, CRUDs de produtos, Atendimento e Cozinha |
| **Atendimento** | `atendimento@briafatia.com` | `admin123` | Visualização de pedidos, atualização de status e envio para produção |
| **Cozinha** | `cozinha@briafatia.com` | `admin123` | Painel KDS em tempo real para alteração de preparo e finalização |

---

## ⚙️ Regras de Negócio e Cálculo de Preços
1. **Regra Meio a Meio:** Quando a pizza é configurada como "Meio a Meio", o valor cobrado reflete o sabor de **maior valor** entre as duas metades selecionadas.
2. **Restrição por Tamanho:** Tamanhos individuais (ex.: *Broto*) travam o modo de montagem em 1 sabor apenas.
3. **Validação Backend:** O valor final é recalculado de forma independente no servidor (`finalizar.php`), impedindo qualquer alteração de preços no lado do cliente.

---

## 🧪 Matriz de Testes Realizados

- [x] **Test 01:** Autenticação por hash de senha seguro (`password_verify`).
- [x] **Test 02:** Bloqueio de acesso por perfil (usuário Cozinha/Atendimento impedido de acessar `/admin`).
- [x] **Test 03:** Proteção contra manipulação CSRF nos formulários de montagem, checkout e admin.
- [x] **Test 04:** Cálculo backend de pizza personalizada (tamanho + massa + ingredientes + borda + molhos + taxa).
- [x] **Test 05:** Checkout completo com escolha de Pagamento (Pix com QR Code, Cartão, Dinheiro com troco dinâmico).
- [x] **Test 06:** Exibição imediata no painel KDS da Cozinha com alerta sonoro e tags detalhadas de produção.
- [x] **Test 07:** Integração financeira direta entre conclusão de pedidos e fluxo de caixa (`caixa_movimentacoes`).
- [x] **Test 08:** Painel de Atendimento com filtros de status, busca rápida, disparo via WhatsApp e impressão de cupom térmico (80mm).
- [x] **Test 09:** Gráficos analíticos Chart.js no Dashboard com métricas de faturamento e ingredientes mais pedidos.
- [x] **Test 10:** CRUD completo de Tamanhos, Massas, Bordas, Molhos, Sabores e Ingredientes.