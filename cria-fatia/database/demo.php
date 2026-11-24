<?php
// Uso exclusivo em banco de demonstração: php database/demo.php --populate|--clean
if (PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', ['--populate', '--clean'], true)) {
    fwrite(STDERR, "Uso: php database/demo.php --populate|--clean\n"); exit(1);
}
if (getenv('APP_ENV') !== 'demo' || getenv('ALLOW_DEMO_DATA') !== '1') {
    fwrite(STDERR, "Bloqueado: defina APP_ENV=demo e ALLOW_DEMO_DATA=1 em um banco isolado.\n"); exit(2);
}
require __DIR__ . '/../config/conexao.php';
$pdo->beginTransaction();
try {
    if ($argv[1] === '--clean') {
        $ids = $pdo->query('SELECT id FROM pedidos WHERE demo = 1')->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM caixa_movimentacoes WHERE pedido_id IN ($marks)")->execute($ids);
            $pdo->prepare("DELETE FROM pedidos WHERE id IN ($marks)")->execute($ids);
        }
        $pdo->commit(); fwrite(STDOUT, count($ids) . " pedidos demo removidos.\n"); exit;
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM pedidos WHERE demo=1')->fetchColumn() > 0) throw new RuntimeException('Já existem pedidos demo; execute --clean primeiro.');
    $tamanho = $pdo->query('SELECT id, preco_base FROM tamanhos WHERE disponivel=1 ORDER BY id LIMIT 1')->fetch();
    $massa = $pdo->query('SELECT id, preco_adicional FROM tipos_massas WHERE disponivel=1 ORDER BY id LIMIT 1')->fetch();
    $borda = $pdo->query('SELECT id, preco FROM bordas WHERE disponivel=1 ORDER BY id LIMIT 1')->fetch();
    $ings = $pdo->query("SELECT id, preco_adicional FROM ingredientes WHERE disponivel=1 ORDER BY tipo_item, id LIMIT 8")->fetchAll();
    if (!$tamanho || !$massa || !$borda || !$ings) throw new RuntimeException('Cadastre tamanho, massa, borda e ingredientes antes de popular.');
    $cenarios = [
        ['Tradicional','pix','pendente',2], ['Personalizada','dinheiro','em_producao',3], ['Oito ingredientes','pix','pronto',8],
        ['Com acompanhamentos','cartao','entregue',4], ['Pix simulado','pix','entregue',2], ['Dinheiro troco suficiente','dinheiro','pendente',3],
        ['Em preparo','cartao','em_producao',2], ['Entregue','dinheiro','entregue',2], ['Cancelado','pix','cancelado',2]
    ];
    $insP = $pdo->prepare("INSERT INTO pedidos (codigo_acompanhamento,token_acesso,cliente_nome,cliente_telefone,tipo_entrega,forma_pagamento,taxa_entrega,troco_para,valor_total,status,demo,observacoes) VALUES (?,?,?,?,?,?,?,?,?,?,1,?)");
    $insI = $pdo->prepare("INSERT INTO pedido_itens (pedido_id,tamanho_id,borda_id,massa_id,divisao,preco_item) VALUES (?,?,?,?,'inteira',?)");
    $insC = $pdo->prepare("INSERT INTO pedido_item_customizacoes (pedido_item_id,metade,ingrediente_id,acao) VALUES (?,1,?,'adicionar')");
    foreach ($cenarios as $n => [$nome,$pag,$status,$qtd]) {
        $selecionados = array_slice($ings, 0, min($qtd, count($ings))); $subtotal=(float)$tamanho['preco_base']+(float)$massa['preco_adicional']+(float)$borda['preco'];
        foreach($selecionados as $i) $subtotal += (float)$i['preco_adicional'];
        $total=$subtotal+5; $troco=$pag==='dinheiro' ? ceil($total/20)*20 : null;
        $codigo='DEMO-'.str_pad((string)($n+1),3,'0',STR_PAD_LEFT); $insP->execute([$codigo,bin2hex(random_bytes(32)),'Cliente Demo '.($n+1),'11999990000','entrega',$pag,5,$troco,$total,$status,$nome]);
        $pedido=(int)$pdo->lastInsertId(); $insI->execute([$pedido,$tamanho['id'],$borda['id'],$massa['id'],$subtotal]); $item=(int)$pdo->lastInsertId();
        foreach($selecionados as $i) $insC->execute([$item,$i['id']]);
    }
    $pdo->commit(); fwrite(STDOUT, count($cenarios) . " pedidos demo criados. A tentativa de troco insuficiente deve ser demonstrada no checkout e não é persistida.\n");
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); fwrite(STDERR, $e->getMessage()."\n"); exit(3); }

