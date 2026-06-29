<?php
require_once 'config/database.php';
try {
    $db = Database::get();
    
    // Buscar o usuário
    $email = 'faustinosdg@gmail.com';
    $stmt = $db->prepare("SELECT id, email, senha FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Usuário encontrado!\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Hash no banco: " . $user['senha'] . "\n";
        
        $senha_fornecida = '!@Jeane&w#1';
        $valida = password_verify($senha_fornecida, $user['senha']);
        echo "A senha '$senha_fornecida' é válida? " . ($valida ? "SIM" : "NÃO") . "\n";
        
        // Se não for válida, vamos testar variações
        if (!$valida) {
            $variacoes = [
                '!@Jeane&w#1',
                '!@Jeane&w#1 ',
                ' !@Jeane&w#1',
                '!@190118!',
                '!@190118',
                'faustinosdg',
                'faustinosdg@gmail.com',
            ];
            echo "\nTestando variações:\n";
            foreach ($variacoes as $v) {
                if (password_verify($v, $user['senha'])) {
                    echo " -> A senha '$v' é VÁLIDA!\n";
                }
            }
        }
    } else {
        echo "Usuário não encontrado!\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
