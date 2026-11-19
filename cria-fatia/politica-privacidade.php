<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 mb-5">
            <h1 class="h3 fw-bold mb-4 pb-2 border-bottom text-warning">
                <i class="bi bi-shield-lock-fill me-2"></i>Política de Privacidade e LGPD
            </h1>

            <p class="lead text-muted fs-6">
                No <strong>Cria Fatia</strong>, valorizamos a transparência e a segurança das suas informações. Esta política descreve como tratamos os dados necessários para o atendimento dos seus pedidos de pizza.
            </p>

            <h5 class="fw-bold mt-4 text-dark">1. Dados Coletados</h5>
            <p>Para a entrega e preparo do seu pedido, coletamos apenas os dados estritamente necessários:</p>
            <ul>
                <li><strong>Nome do Cliente:</strong> Para identificar a sua comanda.</li>
                <li><strong>Telefone (WhatsApp):</strong> Para notificações sobre o status da pizza e confirmação de entrega.</li>
                <li><strong>Endereço de Entrega:</strong> Caso escolha a opção Delivery.</li>
                <li><strong>Forma de Pagamento:</strong> Registro de método escolhido (Pix, Cartão ou Dinheiro).</li>
            </ul>

            <h5 class="fw-bold mt-4 text-dark">2. Segurança e LGPD (Lei nº 13.709/2018)</h5>
            <p>
                Seus dados não são vendidos, alugados ou transferidos a terceiros. As senhas de usuários internos (atendimento, cozinha e admin) são criptografadas com algoritmos seguros (Bcrypt/Argon2).
            </p>

            <h5 class="fw-bold mt-4 text-dark">3. Cookies e Preferências</h5>
            <p>
                Utilizamos armazenamento local exclusivamente para lembrar o tema visual escolhido (Claro, Escuro, Italiano ou Amber) e manter sua sessão ativa de forma segura.
            </p>

            <h5 class="fw-bold mt-4 text-dark">4. Contato do Encarregado de Dados</h5>
            <p class="mb-4">
                Dúvidas sobre o tratamento de dados no projeto acadêmico podem ser encaminhadas para a equipe de desenvolvimento.
            </p>

            <div class="pt-3 border-top d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-warning px-4 fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Voltar ao Início
                </a>
                <a href="<?php echo BASE_URL; ?>termos-de-uso.php" class="btn btn-outline-secondary">
                    Ver Termos de Uso
                </a>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

