<?php
/**
 * Lógica de Assinatura e Trial — SaaS de Roteiros
 * Todas as regras de negócio de acesso, limites e subscription ficam aqui.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/planos.php';
require_once __DIR__ . '/../includes/helpers.php';

// =========================================================================
// CONSULTA DE DADOS DO USUÁRIO
// =========================================================================

/**
 * Retorna dados completos de assinatura de um usuário.
 * Inclui dias de trial, limites, etc.
 */
function getDadosAssinatura(string $userId): array
{
    $db   = Database::get();
    $stmt = $db->prepare(
        "SELECT trial_started_at, subscription_status, subscription_plan, subscription_expires_at
         FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['erro' => 'Usuário não encontrado'];
    }

    $status           = $user['subscription_status'] ?? 'trial';
    $trialStart       = $user['trial_started_at'] ?? date('Y-m-d H:i:s');
    $diasDesdeCadastro = (int) (new DateTime())->diff(new DateTime($trialStart))->days;
    $diasRestantes     = max(0, TRIAL_DIAS - $diasDesdeCadastro);
    $limiteHoje        = calcularLimiteDiario($diasDesdeCadastro, $status);

    // Contar roteiros criados hoje por este usuário
    $hoje       = date('Y-m-d');
    $stmtHoje   = $db->prepare(
        "SELECT COUNT(*) FROM roteiros WHERE user_id = ? AND DATE(created_at) = ?"
    );
    $stmtHoje->execute([$userId, $hoje]);
    $criadosHoje = (int) $stmtHoje->fetchColumn();

    $podeCrear = ($limiteHoje === PHP_INT_MAX)
        ? true
        : ($criadosHoje < $limiteHoje);

    return [
        'status'            => $status,
        'plan'              => $user['subscription_plan'],
        'trial_started_at'  => $trialStart,
        'expires_at'        => $user['subscription_expires_at'],
        'dias_trial'        => $diasDesdeCadastro,
        'dias_restantes'    => $diasRestantes,
        'trial_expirado'    => ($status === 'trial' && $diasDesdeCadastro >= TRIAL_DIAS),
        'limite_hoje'       => $limiteHoje === PHP_INT_MAX ? null : $limiteHoje,
        'criados_hoje'      => $criadosHoje,
        'pode_criar'        => $podeCrear,
        'assinante_ativo'   => ($status === 'active'),
    ];
}

// =========================================================================
// VERIFICAÇÕES DE LIMITE
// =========================================================================

/**
 * Verifica se o usuário pode criar um novo roteiro agora.
 * Retorna array com 'ok', 'motivo', 'limite', 'usados', 'restantes'.
 */
function verificarLimiteDiario(string $userId): array
{
    $dados = getDadosAssinatura($userId);

    if (isset($dados['erro'])) {
        return ['ok' => false, 'motivo' => 'erro_usuario'];
    }

    // Assinante ativo: sem limite
    if ($dados['assinante_ativo']) {
        return ['ok' => true, 'motivo' => 'assinatura_ativa', 'limite' => null, 'usados' => $dados['criados_hoje'], 'restantes' => null];
    }

    // Trial expirado
    if ($dados['trial_expirado']) {
        return ['ok' => false, 'motivo' => 'trial_expirado', 'limite' => 0, 'usados' => $dados['criados_hoje'], 'restantes' => 0];
    }

    // Trial ativo — verificar limite do dia
    $limite    = calcularLimiteDiario($dados['dias_trial'], $dados['status']);
    $usados    = $dados['criados_hoje'];
    $restantes = max(0, $limite - $usados);

    if ($usados >= $limite) {
        return ['ok' => false, 'motivo' => 'limite_diario_atingido', 'limite' => $limite, 'usados' => $usados, 'restantes' => 0];
    }

    return ['ok' => true, 'motivo' => 'pode_criar', 'limite' => $limite, 'usados' => $usados, 'restantes' => $restantes];
}

/**
 * Calcula o limite diário de roteiros baseado no dia do trial e status.
 * Retorna PHP_INT_MAX para assinantes ativos (sem limite).
 */
function calcularLimiteDiario(int $diasDesdeCadastro, string $status): int
{
    if ($status === 'active') return PHP_INT_MAX;

    return match(true) {
        $diasDesdeCadastro === 0 => TRIAL_LIMITE_DIA_1,  // Dia 1: 35
        $diasDesdeCadastro === 1 => TRIAL_LIMITE_DIA_2,  // Dia 2: 10
        $diasDesdeCadastro < TRIAL_DIAS => TRIAL_LIMITE_DIA_N,  // Dia 3–35: 4
        default => 0,  // Trial expirado
    };
}

/**
 * Verifica se o usuário ainda pode fazer upload de arquivos de conhecimento.
 */
function verificarLimiteConhecimento(string $userId): array
{
    $db   = Database::get();
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM roteiros_conhecimento WHERE user_id = ? AND ativo = TRUE"
    );
    $stmt->execute([$userId]);
    $total = (int) $stmt->fetchColumn();

    return [
        'ok'     => $total < CONHECIMENTO_LIMITE,
        'total'  => $total,
        'limite' => CONHECIMENTO_LIMITE,
    ];
}

// =========================================================================
// ATIVAÇÃO / CANCELAMENTO DE ASSINATURA
// =========================================================================

/**
 * Ativa a assinatura de um usuário após pagamento confirmado.
 */
function ativarAssinatura(
    string $userId,
    string $gateway,
    string $externalId,
    string $plan,
    float  $amount,
    ?string $expiresAt = null,
    string $rawPayload = ''
): void {
    $db = Database::get();

    // Calcular expiração se não fornecida
    if (!$expiresAt) {
        $expiresAt = ($plan === 'anual')
            ? date('Y-m-d H:i:s', strtotime('+1 year'))
            : date('Y-m-d H:i:s', strtotime('+1 month'));
    }

    // Atualizar usuário
    $db->prepare(
        "UPDATE users SET
            subscription_status = 'active',
            subscription_plan = ?,
            subscription_expires_at = ?
         WHERE id = ?"
    )->execute([$plan, $expiresAt, $userId]);

    // Registrar no histórico
    $subId = gerarId();
    $db->prepare(
        "INSERT INTO subscriptions
            (id, user_id, gateway, external_id, plan, status, amount, expires_at, raw_payload)
         VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)
         ON CONFLICT (id) DO NOTHING"
    )->execute([$subId, $userId, $gateway, $externalId, $plan, $amount, $expiresAt, $rawPayload]);
}

/**
 * Cancela/expira a assinatura a partir do external_id do gateway.
 */
function cancelarAssinatura(string $externalId, string $gateway, string $rawPayload = ''): void
{
    $db = Database::get();

    // Encontrar a subscription
    $stmt = $db->prepare(
        "SELECT user_id FROM subscriptions WHERE external_id = ? AND gateway = ? LIMIT 1"
    );
    $stmt->execute([$externalId, $gateway]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub) return;

    // Cancelar
    $db->prepare(
        "UPDATE subscriptions SET status = 'cancelled', cancelled_at = CURRENT_TIMESTAMP
         WHERE external_id = ? AND gateway = ?"
    )->execute([$externalId, $gateway]);

    // Atualizar usuário
    $db->prepare(
        "UPDATE users SET subscription_status = 'expired', subscription_expires_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    )->execute([$sub['user_id']]);
}

// =========================================================================
// CONFIGURAÇÕES DO ABACATE PAY (lidas do banco)
// =========================================================================

function getAbacateConfig(): array
{
    try {
        $db   = Database::get();
        $stmt = $db->query(
            "SELECT abacatepay_api_key, abacatepay_webhook_secret,
                    abacatepay_checkout_mensal, abacatepay_checkout_anual,
                    plano_mensal_preco, plano_anual_preco
             FROM configuracao_empresa WHERE id = 'principal' LIMIT 1"
        );
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cfg ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getAbacateApiKey(): ?string
{
    $cfg = getAbacateConfig();
    $key = $cfg['abacatepay_api_key'] ?? '';
    if (!$key && defined('ABACATE_API_KEY')) $key = ABACATE_API_KEY;
    return $key ?: null;
}

// =========================================================================
// HELPERS DE UI
// =========================================================================

/**
 * Retorna a classe CSS de cor do banner de trial baseado nos dias restantes.
 */
function corBannerTrial(int $diasRestantes): string
{
    if ($diasRestantes <= 7)  return 'danger';
    if ($diasRestantes <= 14) return 'warning';
    return 'neutral';
}
