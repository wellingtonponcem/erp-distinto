<?php
/**
 * Arquivo de Configuração de Ambiente (Template)
 * Renomeie ou copie este arquivo para 'env.php' e configure suas variáveis locais.
 * NUNCA versione o arquivo 'env.php' no git pois ele contém chaves secretas.
 */

// Configurações de Banco de Dados
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'erp-distinto');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações Gerais da Aplicação
define('APP_NAME', 'ERP Distinto');
define('APP_URL', 'http://localhost/erp-distinto');

// Chaves de APIs (Integrações de IA e Gateways de Pagamento)
define('GEMINI_API_KEY', '');
define('GROQ_API_KEY', '');
define('GROQ_MODEL', 'llama-3.3-70b-versatile');
define('OPENROUTER_API_KEY', '');
define('MP_ACCESS_TOKEN', ''); // Mercado Pago Access Token se necessário

// Configurações de Sessão
define('SESSION_NAME', 'distinto_session');
define('SESSION_LIFETIME', 86400); // 1 dia em segundos

