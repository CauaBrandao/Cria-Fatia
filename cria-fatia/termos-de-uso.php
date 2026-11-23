<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 mb-5">
            <h1 class="h3 fw-bold mb-4 pb-2 border-bottom text-warning">
                <i class="bi bi-file-text-fill me-2"></i>Termos de Uso
            </h1>

            <p class="lead text-muted fs-6">
                Bem-vindo ao <strong>Cria Fatia</strong>. Ao utilizar nosso sistema de montagem e pedidos de pizza online, você concorda com as condições abaixo:
            </p>

            <h5 class="fw-bold mt-4 text-dark">1. O Serviço Cria Fatia</h5>
            <p>
                O Cria Fatia é uma plataforma interativa de pedidos gastronômicos artesanais, permitindo ao cliente montar sua pizza escolhendo massas, molhos, queijos, carnes, vegetais e bordas recheadas.
            </p>

            <h5 class="fw-bold mt-4 text-dark">2. Pedidos e Cancelamentos</h5>
            <p>
                Após a confirmação do pedido pelo atendimento e o envio para a cozinha, a preparação é iniciada imediatamente. Cancelamentos devem ser solicitados antes do status "Em Preparo".
            </p>

            <h5 class="fw-bold mt-4 text-dark">3. Pagamentos e Cobrança</h5>
            <p>
                O sistema registra a forma de pagamento escolhida. O pagamento via <strong>Pix</strong> não é processado nem confirmado automaticamente; confirme a chave e o valor diretamente com a equipe antes de pagar. <strong>Cartão</strong> e <strong>Dinheiro</strong> são combinados na entrega ou retirada.
            </p>

            <h5 class="fw-bold mt-4 text-dark">4. Natureza do Projeto</h5>
            <p class="mb-4">
                Este sistema integra as atividades avaliativas do Projeto Integrador Senac – TSI (Tecnologia em Sistemas para Internet).
            </p>

            <div class="pt-3 border-top d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-warning px-4 fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Voltar ao Início
                </a>
                <a href="<?php echo BASE_URL; ?>politica-privacidade.php" class="btn btn-outline-secondary">
                    Política de Privacidade
                </a>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
