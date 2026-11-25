<?php
// Funções auxiliares e de segurança do sistema Cria Fatia

function sanitizeInput($data) {
    if (is_null($data)) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatarMoeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function gerarCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function checarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function checarPerfil($perfisPermitidos = []) {
    checarLogin();
    if (!in_array($_SESSION['usuario_perfil'], $perfisPermitidos)) {
        header('Location: ' . BASE_URL . 'index.php?erro=acesso_negado');
        exit;
    }
}

// Funções de Auditoria e Histórico
function registrarAuditoria($pdo, $acao, $modulo, $registro_id = null, $valor_antigo = null, $valor_novo = null, $descricao = '') {
    if (!isset($_SESSION['usuario_id'])) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO historico_alteracoes (usuario_id, acao, modulo, registro_id, valor_antigo, valor_novo, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['usuario_id'],
            $acao,
            $modulo,
            $registro_id,
            is_array($valor_antigo) ? json_encode($valor_antigo) : $valor_antigo,
            is_array($valor_novo) ? json_encode($valor_novo) : $valor_novo,
            $descricao
        ]);
    } catch (Exception $e) {
        // Silenciar erro de auditoria para não quebrar fluxo principal
    }
}

// Rótulos legíveis para os status dos pedidos
function rotuloStatus($status) {
    $rotulos = [
        'pendente'    => 'Pendente',
        'em_producao' => 'Em produção',
        'pronto'      => 'Pronto',
        'entregue'    => 'Entregue',
        'finalizado'  => 'Finalizado',
        'cancelado'   => 'Cancelado',
    ];
    return $rotulos[$status] ?? ucfirst((string)$status);
}

// Remove acentos e padroniza o texto para comparações
function normalizarTexto($texto) {
    $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
    $mapa = ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
             'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
    $texto = strtr($texto, $mapa);
    return preg_replace('/[^a-z0-9]+/', ' ', $texto);
}

/**
 * Descobre os ingredientes de um sabor do cardápio.
 * Usa a tabela sabor_ingredientes e, como complemento, os ingredientes citados
 * no nome/descrição do sabor (ex.: "Calabresa fatiada, cebola e azeitonas").
 * $ingredientes = lista de ingredientes disponíveis (id, nome, preco_adicional).
 * Retorna os ids encontrados, na ordem do cadastro.
 */
function ingredientesDoSabor($pdo, array $sabor, array $ingredientes) {
    $ids = [];
    try {
        $stmt = $pdo->prepare("SELECT ingrediente_id FROM sabor_ingredientes WHERE sabor_id = ?");
        $stmt->execute([(int)$sabor['id']]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Exception $e) {
        $ids = [];
    }

    $texto = ' ' . normalizarTexto(($sabor['nome'] ?? '') . ' ' . ($sabor['descricao'] ?? '')) . ' ';
    $primeiraPalavraUsada = [];

    // Ingredientes ordenados por id: em nomes parecidos (ex.: "Azeitona preta" e "Azeitona verde"), vale o primeiro cadastro
    usort($ingredientes, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);

    // 1ª passada: nome completo citado no texto (ex.: "frango desfiado", "mussarela")
    foreach ($ingredientes as $ing) {
        $nome = trim(normalizarTexto($ing['nome']));
        if ($nome !== '' && strpos($texto, ' ' . $nome . ' ') !== false) {
            $ids[] = (int)$ing['id'];
            $primeiraPalavraUsada[explode(' ', $nome)[0]] = true;
        }
    }
    // 2ª passada: só a primeira palavra, aceitando plural (ex.: "ovos" -> "Ovo cozido", "azeitonas" -> "Azeitona preta")
    foreach ($ingredientes as $ing) {
        $nome = trim(normalizarTexto($ing['nome']));
        $palavra = explode(' ', $nome)[0];
        if (strlen($palavra) < 3 || isset($primeiraPalavraUsada[$palavra])) {
            continue;
        }
        if (preg_match('/ ' . preg_quote($palavra, '/') . '(s|es)? /', $texto)) {
            $ids[] = (int)$ing['id'];
            $primeiraPalavraUsada[$palavra] = true;
        }
    }

    $disponiveis = array_map(fn($i) => (int)$i['id'], $ingredientes);
    return array_values(array_unique(array_intersect($ids, $disponiveis)));
}

// ===================== PIX DE DEMONSTRAÇÃO =====================
// Projeto acadêmico: a chave abaixo é fictícia (chave aleatória que não pertence a ninguém).
// Nenhum pagamento é processado ou confirmado pelo sistema.
const PIX_CHAVE_DEMO = '3f1c9a2e-7b4d-4e8a-9c51-6d0b2e8f4a17';
const PIX_FAVORECIDO_DEMO = 'CRIA FATIA DEMO';
const PIX_CIDADE_DEMO = 'SAO PAULO';

function pixCampo($id, $valor) {
    return $id . str_pad((string)strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}

function pixCrc16($payload) {
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($payload); $i < $n; $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($b = 0; $b < 8; $b++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

// Gera o código "Pix Copia e Cola" (padrão BR Code do Banco Central)
function gerarPixCopiaECola($valor, $txid = '***') {
    $txid = preg_replace('/[^A-Za-z0-9]/', '', (string)$txid) ?: '***';
    $payload = pixCampo('00', '01')
        . pixCampo('26', pixCampo('00', 'br.gov.bcb.pix') . pixCampo('01', PIX_CHAVE_DEMO))
        . pixCampo('52', '0000')
        . pixCampo('53', '986')
        . pixCampo('54', number_format((float)$valor, 2, '.', ''))
        . pixCampo('58', 'BR')
        . pixCampo('59', PIX_FAVORECIDO_DEMO)
        . pixCampo('60', PIX_CIDADE_DEMO)
        . pixCampo('62', pixCampo('05', substr($txid, 0, 25)))
        . '6304';
    return $payload . pixCrc16($payload);
}

function urlQrCode($texto, $tamanho = 200) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$tamanho . 'x' . (int)$tamanho . '&margin=8&data=' . rawurlencode($texto);
}

function registrarStatusPedido($pdo, $pedido_id, $status_anterior, $novo_status, $observacao = '') {
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    try {
        $stmt = $pdo->prepare("INSERT INTO historico_status_pedidos (pedido_id, status_anterior, novo_status, usuario_id, observacao) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pedido_id, $status_anterior, $novo_status, $usuario_id, $observacao]);
    } catch (Exception $e) {
        // Silenciar
    }
}

function saldoDisponivelTroco(PDO $pdo, int $caixaId): float {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN cm.tipo IN ('entrada','liberacao_troco') THEN cm.valor WHEN cm.tipo = 'venda' AND p.forma_pagamento = 'dinheiro' THEN cm.valor WHEN cm.tipo IN ('saida','reserva_troco') THEN -cm.valor ELSE 0 END), 0) FROM caixa_movimentacoes cm LEFT JOIN pedidos p ON p.id = cm.pedido_id WHERE cm.caixa_id = ?");
    $stmt->execute([$caixaId]);
    return (float)$stmt->fetchColumn();
}
