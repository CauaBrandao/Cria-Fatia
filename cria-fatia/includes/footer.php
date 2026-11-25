</div><!-- /.container principal -->

<!-- ===================== FOOTER COMPACTO E ELEGANTE ===================== -->
<footer class="cf-footer mt-5">
    <div class="container py-4">
        <div class="row align-items-center gy-3">
            <!-- Coluna 1: Marca & Slogan -->
            <div class="col-md-4 text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                    <i class="bi bi-pie-chart-fill text-warning fs-4"></i>
                    <span class="fs-5 fw-bold text-white">Cria Fatia</span>
                </div>
                <p class="text-white-50 small mb-0">"Monte do seu jeito, peça sem erro!"</p>
            </div>

            <!-- Coluna 2: Navegação Rápida -->
            <div class="col-md-4 text-center">
                <div class="cf-footer-nav d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?php echo BASE_URL; ?>" class="cf-link">Início</a>
                    <a href="<?php echo BASE_URL; ?>#cardapio" class="cf-link">Cardápio</a>
                    <a href="<?php echo BASE_URL; ?>cliente/montar.php" class="cf-link">Montar Pizza</a>
                    <a href="<?php echo BASE_URL; ?>cliente/pedido.php" class="cf-link">Acompanhar Pedido</a>
                </div>
            </div>

            <!-- Coluna 3: Links Institucionais / Termos -->
            <div class="col-md-4 text-center text-md-end">
                <div class="cf-footer-nav d-flex flex-wrap justify-content-center justify-content-md-end gap-3 mb-1">
                    <a href="<?php echo BASE_URL; ?>politica-privacidade.php" class="cf-link">Privacidade</a>
                    <a href="<?php echo BASE_URL; ?>termos-de-uso.php" class="cf-link">Termos de Uso</a>
                    <a href="#" class="cf-link" data-bs-toggle="modal" data-bs-target="#modalCookies">Cookies</a>
                </div>
                <div class="cf-badges-compact d-flex justify-content-center justify-content-md-end gap-2 text-white-50" style="font-size: 0.75rem;">
                    <span><i class="bi bi-shield-check text-warning"></i> LGPD</span>
                    <span>•</span>
                    <span><i class="bi bi-lock text-warning"></i> Conexão Segura</span>
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-25 my-3">

        <!-- Linha de Direitos e Senac -->
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-white-50">
                    &copy; <?php echo date('Y'); ?> <strong>Cria Fatia</strong> – Todos os direitos reservados.
                </small>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <small class="text-white-50">
                    <i class="bi bi-mortarboard me-1 text-warning"></i> Projeto Integrador Senac &bull; TSI – Tecnologia em Sistemas para Internet
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- ===================== MODAL: POLÍTICA DE COOKIES ===================== -->
<div class="modal fade" id="modalCookies" tabindex="-1" aria-labelledby="modalCookiesLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCookiesLabel"><i class="bi bi-cookie me-2 text-warning"></i>Política de Cookies</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        <p>O <strong>Cria Fatia</strong> utiliza apenas cookies essenciais para funcionamento do carrinho, login de usuários e persistência do tema visual escolhido.</p>
        <p class="text-muted mb-0">Não utilizamos cookies de publicidade invasiva nem compartilhamos dados com terceiros.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning btn-sm fw-bold px-3" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/app.js'); ?>"></script>
</body>
</html>
