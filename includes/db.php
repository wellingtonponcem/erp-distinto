<?php
/**
 * Conexão com Neon PostgreSQL via PDO + configurações globais
 */

// ── BASE_PATH: detecta automaticamente se o app está em subfolder ou na raiz ──
if (!defined('BASE_PATH')) {
    $doc_root = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $app_root = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
    
    // Fallback para Hostinger/ambientes onde realpath pode falhar ou ser inconsistente
    $base = '';
    if (strpos($app_root, $doc_root) === 0) {
        $base = str_replace($doc_root, '', $app_root);
    }
    
    // Se a URL requisitada não começa com /sistema, não há subfolder ativo no Hostinger
    $request_uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if ($base !== '' && strpos($request_uri, $base . '/') !== 0 && $request_uri !== $base) {
        $base = '';
    }
    
    define('BASE_PATH', rtrim($base, '/')); 
}

// Neon SNI workaround: endpoint ID nas options (libpq não usa URL encoding)
define('DB_DSN', 'pgsql:host=ep-divine-star-acx7crrn-pooler.sa-east-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require;options=endpoint=ep-divine-star-acx7crrn');
define('DB_USER', 'neondb_owner');
define('DB_PASS', 'npg_KZkR3eNUW7qJ');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => true,
            ]);
        } catch (PDOException $e) {
            die('Erro de conexão com o banco: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function get_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare('SELECT value FROM settings WHERE key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (PDOException) {
        return $default;
    }
}

function get_settings_all(): array {
    try {
        $stmt = db()->query('SELECT key, value FROM settings');
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    } catch (PDOException) {
        return [];
    }
}

function set_setting(string $key, string $value): void {
    $stmt = db()->prepare(
        'INSERT INTO settings (key, value) VALUES (:key, :value)
         ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value'
    );
    $stmt->execute([':key' => $key, ':value' => $value]);
}

function get_projects(string $category = '', bool $order_by_menu = true): array {
    $sql = 'SELECT p.*, pc.slug AS category_slug, pc.name AS category_name
            FROM projects p
            LEFT JOIN project_category_relations r ON p.id = r.project_id
            LEFT JOIN project_categories pc ON r.category_id = pc.id
            WHERE p.status = \'published\'';
    $params = [];
    if ($category) {
        $sql .= ' AND pc.slug = :cat';
        $params[':cat'] = $category;
    }
    $sql .= $order_by_menu ? ' ORDER BY p.menu_order ASC, p.id ASC' : ' ORDER BY p.id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_project(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_project_gallery(int $project_id): array {
    $stmt = db()->prepare(
        'SELECT * FROM project_gallery WHERE project_id = :id ORDER BY sort_order ASC'
    );
    $stmt->execute([':id' => $project_id]);
    return $stmt->fetchAll();
}

function get_project_categories(): array {
    $stmt = db()->query('SELECT * FROM project_categories ORDER BY name ASC');
    return $stmt->fetchAll();
}

function get_wedding_projects(): array {
    return get_projects('casamentos');
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Retorna URL absoluta dentro do app (respeita subfolder) */
function url(string $path = ''): string {
    return BASE_PATH . '/' . ltrim($path, '/');
}

/** Retorna URL de asset (respeita subfolder) */
function asset(string $path): string {
    return BASE_PATH . '/assets/' . ltrim($path, '/');
}
