<?php
/**
 * Script temporário para corrigir o env.php de produção na Hostinger
 */
header('Content-Type: text/plain; charset=utf-8');

$env_file = __DIR__ . '/config/env.php';

if (!file_exists($env_file)) {
    echo "Erro: O arquivo $env_file não foi encontrado para ser atualizado!\n";
    exit;
}

$novo_conteudo = '<?php
/**
 * Arquivo de Configuração de Ambiente (Produção Hostinger)
 * Atualizado via script de reparo Antigravity
 */

// Configurações de Banco de Dados
define(\'DB_HOST\', \'ep-crimson-sun-ac4t9f9a.sa-east-1.aws.neon.tech\');
define(\'DB_PORT\', 5432);
define(\'DB_NAME\', \'neondb\');
define(\'DB_USER\', \'neondb_owner\');
define(\'DB_PASS\', \'npg_3fXdCHMbS2xJ\');

// Configurações Gerais da Aplicação
define(\'APP_NAME\', \'ERP Distinto\');
define(\'APP_URL\', \'https://wedistinto.com/sistema\');

// Chaves de APIs (Integrações de IA e Gateways de Pagamento)
define(\'GEMINI_API_KEY\', \'\');
define(\'GROQ_API_KEY\', \'\');
define(\'GROQ_MODEL\', \'llama-3.3-70b-versatile\');
define(\'OPENROUTER_API_KEY\', \'\');
define(\'MP_ACCESS_TOKEN\', \'\');

// Configurações de Sessão
define(\'SESSION_NAME\', \'distinto_session\');
define(\'SESSION_LIFETIME\', 86400); // 1 dia em segundos
';

try {
    // Faz backup do arquivo antigo antes
    copy($env_file, $env_file . '.bak');
    echo "Backup do env.php antigo criado em config/env.php.bak\n";
    
    // Grava o novo conteúdo
    $res = file_put_contents($env_file, $novo_conteudo);
    if ($res !== false) {
        echo "Sucesso! O arquivo config/env.php foi atualizado com as credenciais corretas do banco do ERP.\n";
    } else {
        echo "Erro: Não foi possível escrever no arquivo config/env.php. Verifique permissões.\n";
    }
} catch (Exception $e) {
    echo "Erro ocorrido: " . $e->getMessage() . "\n";
}
