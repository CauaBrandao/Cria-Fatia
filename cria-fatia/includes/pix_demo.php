<?php
/**
 * Bloco de pagamento Pix de DEMONSTRAÇÃO (projeto acadêmico).
 * Mostra QR Code + "Pix Copia e Cola" gerados com uma chave fictícia.
 * Nenhum pagamento é processado ou confirmado pelo sistema.
 */
function renderizarPixDemo($valor, $txid = '***', $atributos = '') {
    $codigo = gerarPixCopiaECola($valor, $txid);
    $idCampo = 'pix_' . substr(md5($codigo), 0, 8);
    ?>
    <div class="cf-pix" <?php echo $atributos; ?>>
        <div class="row align-items-center g-3">
            <div class="col-sm-5 text-center">
                <div class="d-inline-block p-2 bg-white border rounded-3 shadow-sm">
                    <img src="<?php echo htmlspecialchars(urlQrCode($codigo, 200)); ?>" width="170" height="170" alt="QR Code Pix de demonstração" loading="lazy">
                </div>
                <div class="small text-muted mt-2">Escaneie com o app do seu banco</div>
            </div>
            <div class="col-sm-7">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">Valor</span>
                    <strong class="fs-5 text-success"><?php echo formatarMoeda($valor); ?></strong>
                </div>
                <label class="small fw-bold mb-1" for="<?php echo $idCampo; ?>">Pix Copia e Cola</label>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control font-monospace" id="<?php echo $idCampo; ?>" value="<?php echo htmlspecialchars($codigo); ?>" readonly>
                    <button type="button" class="btn btn-success fw-bold" onclick="copiarPixDemo('<?php echo $idCampo; ?>', this)"><i class="bi bi-clipboard me-1"></i>Copiar</button>
                </div>
                <div class="small text-muted">
                    <div><strong>Chave (aleatória):</strong> <span class="font-monospace"><?php echo htmlspecialchars(PIX_CHAVE_DEMO); ?></span></div>
                    <div><strong>Favorecido:</strong> Cria Fatia – Bella Roza Pizzaria</div>
                </div>
            </div>
        </div>
        <div class="alert alert-info small mt-3 mb-0 py-2">
            <i class="bi bi-info-circle me-1"></i> <strong>Ambiente de demonstração (projeto acadêmico):</strong> esta chave Pix é fictícia e nenhum pagamento é cobrado ou confirmado pelo sistema.
        </div>
    </div>
    <?php
}

function scriptCopiarPixDemo() {
    ?>
    <script>
    function copiarPixDemo(idCampo, botao) {
        const campo = document.getElementById(idCampo);
        const avisar = () => {
            const original = botao.innerHTML;
            botao.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiado!';
            setTimeout(() => { botao.innerHTML = original; }, 2500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(campo.value).then(avisar).catch(() => { campo.select(); document.execCommand('copy'); avisar(); });
        } else {
            campo.select(); document.execCommand('copy'); avisar();
        }
    }
    </script>
    <?php
}
