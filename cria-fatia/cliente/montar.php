<?php
require_once __DIR__ . '/../includes/header.php';

$tamanhos = $pdo->query("SELECT * FROM tamanhos WHERE disponivel = 1 ORDER BY id ASC")->fetchAll();
$massas = $pdo->query("
    SELECT tm.*
    FROM tipos_massas tm
    INNER JOIN (
        SELECT MIN(id) AS id
        FROM tipos_massas
        WHERE disponivel = 1
        GROUP BY LOWER(TRIM(nome))
    ) unicas ON unicas.id = tm.id
    ORDER BY tm.nome ASC
")->fetchAll();
$bordas = $pdo->query("SELECT * FROM bordas WHERE disponivel = 1 ORDER BY id ASC")->fetchAll();
$ingredientes = $pdo->query("SELECT * FROM ingredientes WHERE disponivel = 1 AND tipo_item = 'ingrediente' ORDER BY nome ASC")->fetchAll();
$acompanhamentos = $pdo->query("SELECT * FROM ingredientes WHERE disponivel = 1 AND tipo_item = 'acompanhamento' ORDER BY nome ASC")->fetchAll();
$molhos = $pdo->query("SELECT * FROM molhos WHERE disponivel = 1 ORDER BY nome ASC")->fetchAll();
$token = gerarCsrfToken();
?>

<div class="row mb-4 align-items-center">
    <div class="col-lg-8">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2"><i class="bi bi-stars me-1"></i> Monte sua pizza do seu jeito</span>
        <h1 class="display-5 fw-bold text-dark mb-2">Crie sua Pizza</h1>
        <p class="text-muted lead fs-6">Escolha os ingredientes que deseja. Você monta sua própria combinação, ingrediente por ingrediente.</p>
    </div>
</div>

<form action="finalizar.php" method="POST" id="formMontador">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4 border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4"><h4 class="mb-1 fw-bold">01. Escolha o tamanho</h4><small class="text-muted">O preço base da pizza varia conforme o tamanho.</small></div>
                <div class="card-body p-4"><div class="row g-3">
                    <?php foreach($tamanhos as $index => $t): ?>
                    <div class="col-md-4"><div class="selector-card h-100 p-3 rounded-3 border bg-white text-center cursor-pointer <?php echo $index === 0 ? 'border-warning shadow-sm bg-warning bg-opacity-10 selected' : ''; ?>" onclick="selecionarTamanho(this, <?php echo (int)$t['id']; ?>)">
                        <div class="fw-bold fs-5"><?php echo htmlspecialchars($t['nome']); ?></div><div class="badge bg-warning text-dark mt-2">R$ <?php echo number_format($t['preco_base'],2,',','.'); ?></div>
                    </div></div><?php endforeach; ?>
                </div><select class="form-select d-none" name="tamanho_id" id="tamanho_id" required><?php foreach($tamanhos as $i=>$t): ?><option value="<?php echo (int)$t['id']; ?>" data-preco="<?php echo (float)$t['preco_base']; ?>" <?php echo $i===0?'selected':''; ?>><?php echo htmlspecialchars($t['nome']); ?></option><?php endforeach; ?></select></div>
            </div>

            <div class="card mb-4 border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4"><h4 class="mb-1 fw-bold">02. Escolha o tipo de massa</h4><small class="text-muted">Selecione a massa da sua pizza.</small></div>
                <div class="card-body p-4"><div class="row g-3">
                    <?php foreach ($massas as $i => $massa): ?>
                    <div class="col-md-6"><div class="selector-card h-100 p-3 rounded-3 border bg-white cursor-pointer <?php echo $i === 0 ? 'selected border-warning bg-warning bg-opacity-10' : ''; ?>" onclick="selecionarMassa(this, <?php echo (int)$massa['id']; ?>)">
                        <div class="fw-bold"><?php echo htmlspecialchars($massa['nome']); ?></div>
                        <?php if (!empty($massa['descricao'])): ?><small class="text-muted"><?php echo htmlspecialchars($massa['descricao']); ?></small><?php endif; ?>
                        <div class="text-success small fw-bold mt-1"><?php echo (float)$massa['preco_adicional'] > 0 ? '+ R$ '.number_format($massa['preco_adicional'],2,',','.') : 'Sem adicional'; ?></div>
                    </div></div>
                    <?php endforeach; ?>
                </div><select class="form-select d-none" name="massa_id" id="massa_id" required>
                    <?php foreach ($massas as $i => $massa): ?><option value="<?php echo (int)$massa['id']; ?>" data-preco="<?php echo (float)$massa['preco_adicional']; ?>" <?php echo $i===0?'selected':''; ?>><?php echo htmlspecialchars($massa['nome']); ?></option><?php endforeach; ?>
                </select></div>
            </div>

            <div class="card mb-4 border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h4 class="mb-1 fw-bold">03. Escolha os ingredientes</h4><small class="text-muted">Selecione no máximo 8 ingredientes.</small></div><span id="contador_ingredientes" class="badge bg-dark fs-6" aria-live="polite">0/8 ingredientes selecionados</span></div>
                <div class="card-body p-4"><div id="aviso_limite_ingredientes" class="alert alert-warning small" role="status"><i class="bi bi-info-circle me-1"></i> Cada ingrediente selecionado será incluído na montagem e somado ao preço final.</div>
                    <div class="row g-3">
                    <?php foreach($ingredientes as $ing): ?>
                        <div class="col-md-6"><label class="ingredient-option d-flex align-items-center justify-content-between gap-2 p-3 border rounded-3 h-100 cursor-pointer" for="ingrediente_<?php echo (int)$ing['id']; ?>">
                            <span class="form-check d-flex align-items-center gap-2 mb-0"><input class="form-check-input ingrediente-checkbox mt-0" type="checkbox" name="ingredientes[]" value="<?php echo (int)$ing['id']; ?>" data-preco="<?php echo (float)$ing['preco_adicional']; ?>" id="ingrediente_<?php echo (int)$ing['id']; ?>"><span class="fw-semibold text-dark"><?php echo htmlspecialchars($ing['nome']); ?></span></span>
                            <span class="text-success small fw-bold">+ R$ <?php echo number_format($ing['preco_adicional'],2,',','.'); ?></span>
                        </label></div>
                    <?php endforeach; ?>
                    </div>
                    <?php if (!$ingredientes): ?><p class="text-muted mb-0">Nenhum ingrediente disponível no momento.</p><?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm rounded-4"><div class="card-header bg-white p-4"><h4 class="mb-1 fw-bold">04. Acompanhamentos</h4><small class="text-muted">Opcionais, fora do limite de 8 ingredientes. Máximo de 4.</small></div><div class="card-body p-4"><div class="row g-3">
                <?php foreach($acompanhamentos as $ing): ?><div class="col-md-6"><label class="ingredient-option d-flex align-items-center justify-content-between gap-2 p-3 border rounded-3 h-100" for="acompanhamento_<?php echo (int)$ing['id']; ?>"><span class="form-check mb-0"><input class="form-check-input acompanhamento-checkbox" type="checkbox" name="ingredientes[]" value="<?php echo (int)$ing['id']; ?>" data-preco="<?php echo (float)$ing['preco_adicional']; ?>" id="acompanhamento_<?php echo (int)$ing['id']; ?>"> <span class="fw-semibold"><?php echo htmlspecialchars($ing['nome']); ?></span></span><span class="text-success small fw-bold">+ R$ <?php echo number_format($ing['preco_adicional'],2,',','.'); ?></span></label></div><?php endforeach; ?>
                <?php if (!$acompanhamentos): ?><p class="text-muted mb-0">Nenhum acompanhamento disponível.</p><?php endif; ?>
            </div></div></div>

            <div class="card mb-4 border-0 shadow-sm rounded-4"><div class="card-header bg-white p-4"><h4 class="mb-1 fw-bold">05. Borda recheada</h4><small class="text-muted">Escolha uma opção para finalizar.</small></div><div class="card-body p-4"><div class="row g-3">
                <?php foreach($bordas as $i=>$b): ?><div class="col-md-6"><div class="selector-card h-100 p-3 rounded-3 border bg-white cursor-pointer <?php echo $i===0?'selected border-warning bg-warning bg-opacity-10':''; ?>" onclick="selecionarBorda(this,<?php echo (int)$b['id']; ?>)"><div class="fw-bold"><?php echo htmlspecialchars($b['nome']); ?></div><small class="text-success">+ R$ <?php echo number_format($b['preco'],2,',','.'); ?></small></div></div><?php endforeach; ?>
            </div><select class="form-select d-none" name="borda_id" id="borda_id" required><?php foreach($bordas as $i=>$b): ?><option value="<?php echo (int)$b['id']; ?>" data-preco="<?php echo (float)$b['preco']; ?>" <?php echo $i===0?'selected':''; ?>><?php echo htmlspecialchars($b['nome']); ?></option><?php endforeach; ?></select></div></div>

            <div class="card mb-4 border-0 shadow-sm rounded-4"><div class="card-header bg-white p-4"><h4 class="mb-1 fw-bold">05. Molhos adicionais</h4><small class="text-muted">Opcional — escolha um ou mais.</small></div><div class="card-body p-4"><div class="row g-3">
                <?php foreach($molhos as $molho): ?><div class="col-md-6"><label class="d-flex align-items-center justify-content-between gap-2 p-3 border rounded-3 h-100" for="molho_<?php echo (int)$molho['id']; ?>"><span class="form-check mb-0"><input class="form-check-input molho-checkbox" type="checkbox" name="molhos[]" value="<?php echo (int)$molho['id']; ?>" data-preco="<?php echo (float)$molho['preco_adicional']; ?>" id="molho_<?php echo (int)$molho['id']; ?>"><span class="form-check-label fw-semibold ms-1"><?php echo htmlspecialchars($molho['nome']); ?></span></span><span class="text-success small fw-bold">+ R$ <?php echo number_format($molho['preco_adicional'],2,',','.'); ?></span></label></div><?php endforeach; ?>
            </div></div></div>
        </div>
        <div class="col-lg-5"><div class="card border-0 shadow-lg rounded-4 position-sticky" style="top:2rem;background:#181A20;color:#fff"><div class="card-header bg-warning text-dark fw-bold text-center py-3">Resumo da sua pizza</div><div class="card-body p-4">
            <div class="text-center mb-4">
                <div id="pizza_disc" class="rounded-circle mx-auto d-flex flex-column align-items-center justify-content-center p-3 position-relative shadow" style="width:190px;height:190px;background:#e29b38;color:#542b0b;border:10px solid #d48325;transition:all 0.3s ease;">
                    <div id="pizza_toppings_preview" class="d-flex flex-wrap justify-content-center align-items-center gap-1 overflow-hidden" style="max-height:120px;">
                        <span class="text-dark small fw-bold opacity-75"><i class="bi bi-pizza fs-2 d-block text-center"></i>Selecione ingredientes</span>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tamanho</span><strong id="resumo_tamanho">—</strong></div>
            <div class="mb-3"><div class="text-muted small mb-1">Ingredientes escolhidos</div><div class="fw-semibold text-warning" id="resumo_ingredientes">Nenhum selecionado</div></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Massa</span><strong id="resumo_massa">—</strong></div><div class="d-flex justify-content-between mb-2"><span class="text-muted">Borda</span><strong id="resumo_borda">—</strong></div>
            <div class="mb-3"><div class="text-muted small mb-1">Molhos</div><strong id="resumo_molhos">Nenhum</strong></div>
            <hr class="border-secondary"><div class="d-flex justify-content-between align-items-center mb-4"><span class="fw-bold">Total</span><h2 class="text-success mb-0" id="preco_total_display">R$ 0,00</h2></div>
            <input type="hidden" name="valor_total" id="valor_total_input" value="0.00"><button type="submit" class="btn btn-warning btn-lg w-100 fw-bold py-3"><i class="bi bi-bag-check-fill me-2"></i> Avançar para Checkout</button>
        </div></div></div>
    </div>
</form>
<style>.ingredient-option{cursor:pointer;transition:.15s}.ingredient-option:has(input:checked){border-color:#ffc107!important;background:#fff8df}.selector-card{cursor:pointer}.selector-card.selected{border-color:#ffc107!important}</style>
<script>
function selecionarTamanho(el,id){el.closest('.row').querySelectorAll('.selector-card').forEach(x=>x.classList.remove('selected','border-warning','bg-warning','bg-opacity-10'));el.classList.add('selected','border-warning','bg-warning','bg-opacity-10');document.getElementById('tamanho_id').value=id;calcularPrecoTotal();}
function selecionarMassa(el,id){el.closest('.row').querySelectorAll('.selector-card').forEach(x=>x.classList.remove('selected','border-warning','bg-warning','bg-opacity-10'));el.classList.add('selected','border-warning','bg-warning','bg-opacity-10');document.getElementById('massa_id').value=id;calcularPrecoTotal();}
function selecionarBorda(el,id){el.closest('.row').querySelectorAll('.selector-card').forEach(x=>x.classList.remove('selected','border-warning','bg-warning','bg-opacity-10'));el.classList.add('selected','border-warning','bg-warning','bg-opacity-10');document.getElementById('borda_id').value=id;calcularPrecoTotal();}
const LIMITE_INGREDIENTES = 8;
function atualizarLimiteIngredientes(mostrarAviso = false) {
    const checkboxes = [...document.querySelectorAll('.ingrediente-checkbox')];
    const selecionados = checkboxes.filter(x => x.checked);
    const atingiuLimite = selecionados.length >= LIMITE_INGREDIENTES;

    checkboxes.forEach(x => { x.disabled = atingiuLimite && !x.checked; });

    const contador = document.getElementById('contador_ingredientes');
    contador.textContent = `${selecionados.length}/${LIMITE_INGREDIENTES} ingredientes selecionados`;
    contador.classList.toggle('bg-danger', atingiuLimite);
    contador.classList.toggle('bg-dark', !atingiuLimite);

    const aviso = document.getElementById('aviso_limite_ingredientes');
    if (atingiuLimite || mostrarAviso) {
        aviso.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Limite de 8 ingredientes atingido. Desmarque um ingrediente para escolher outro.';
        aviso.classList.add('alert-danger');
        aviso.classList.remove('alert-warning');
    } else {
        aviso.innerHTML = '<i class="bi bi-info-circle me-1"></i> Cada ingrediente selecionado será incluído na montagem e somado ao preço final.';
        aviso.classList.add('alert-warning');
        aviso.classList.remove('alert-danger');
    }
}
function calcularPrecoTotal(){
    const ts=document.getElementById('tamanho_id'),to=ts.options[ts.selectedIndex];
    let total=parseFloat(to.dataset.preco)||0;
    document.getElementById('resumo_tamanho').textContent=to.text;
    const ms=document.getElementById('massa_id'),mo=ms.options[ms.selectedIndex];
    total+=parseFloat(mo.dataset.preco)||0;
    document.getElementById('resumo_massa').textContent=mo.text;
    const nomes=[];
    const previewContainer=document.getElementById('pizza_toppings_preview');
    if (previewContainer) previewContainer.innerHTML = '';
    document.querySelectorAll('.ingrediente-checkbox:checked, .acompanhamento-checkbox:checked').forEach(x=>{
        total+=parseFloat(x.dataset.preco)||0;
        const nome = x.parentElement.querySelector('span').textContent.trim();
        nomes.push(nome);
        if (previewContainer) {
            const tag = document.createElement('span');
            tag.className = 'badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-50 small m-0.5';
            tag.style.fontSize = '0.72rem';
            tag.textContent = nome;
            previewContainer.appendChild(tag);
        }
    });
    if (nomes.length === 0 && previewContainer) {
        previewContainer.innerHTML = '<span class="text-dark small fw-bold opacity-75"><i class="bi bi-pizza fs-2 d-block text-center"></i>Selecione ingredientes</span>';
    }
    document.getElementById('resumo_ingredientes').textContent=nomes.length?nomes.join(', '):'Nenhum selecionado';
    const bs=document.getElementById('borda_id'),bo=bs.options[bs.selectedIndex];
    total+=parseFloat(bo.dataset.preco)||0;
    document.getElementById('resumo_borda').textContent=bo.text;
    const molhos=[];
    document.querySelectorAll('.molho-checkbox:checked').forEach(x=>{
        total+=parseFloat(x.dataset.preco)||0;
        molhos.push(x.nextElementSibling.textContent.trim());
    });
    document.getElementById('resumo_molhos').textContent=molhos.length?molhos.join(', '):'Nenhum';
    document.getElementById('preco_total_display').textContent='R$ '+total.toFixed(2).replace('.',',');
    document.getElementById('valor_total_input').value=total.toFixed(2);
}
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.ingrediente-checkbox').forEach(x=>x.addEventListener('change',()=>{
        atualizarLimiteIngredientes();
        calcularPrecoTotal();
    }));
    document.querySelectorAll('.ingredient-option').forEach(label=>label.addEventListener('click',event=>{
        const checkbox = label.querySelector('.ingrediente-checkbox');
        if (checkbox?.disabled && !checkbox.checked) {
            event.preventDefault();
            atualizarLimiteIngredientes(true);
        }
    }));
    document.querySelectorAll('.molho-checkbox').forEach(x=>x.addEventListener('change',calcularPrecoTotal));
    document.querySelectorAll('.acompanhamento-checkbox').forEach(x=>x.addEventListener('change',event=>{
        if (document.querySelectorAll('.acompanhamento-checkbox:checked').length > 4) { event.target.checked = false; alert('Selecione no máximo 4 acompanhamentos.'); }
        calcularPrecoTotal();
    }));
    atualizarLimiteIngredientes();
    calcularPrecoTotal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
