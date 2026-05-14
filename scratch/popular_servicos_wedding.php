<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = Database::get();

$produtos = [
    // PLANOS BASE
    [
        'nome' => 'Experiência Heritage',
        'categoria' => 'wedding',
        'tipo' => 'plano',
        'subtitulo' => 'Este é o plano definitivo para casais que não aceitam lacunas. É a garantia de uma cobertura onipresente, focada na construção da herança visual da sua família, do papel à tela.',
        'beneficios' => [
            'Cobertura Documental Completa: Presença ilimitada no evento. Do making of à última música, sem limite de horas.',
            'O Álbum Heritage: Álbum luxo panorâmico no tamanho 25x30cm (aberto 25x60cm), com papel fotográfico de alta gramatura e laminação especial.',
            'Réplicas para a Família (Presente): Inclusão de 02 Mini Álbuns réplicas, ideais para presentear os pais com a mesma qualidade do álbum principal.',
            'Produção Cinematográfica 4K: Filme completo (8 a 12 min) com áudio dos votos e trilha sonora licenciada.',
            'Imagens Aéreas (Drone): Perspectivas cinematográficas para contextualizar o local do seu "sim".',
            'Ecossistema Digital e Físico: Galeria online vitalícia, e pen drive personalizado.'
        ],
        'preco' => 7900.00,
        'condicoes' => 'Condição especial, amigos Lagoinha Ilha.'
    ],
    [
        'nome' => 'Experiência Cinematic',
        'categoria' => 'wedding',
        'tipo' => 'plano',
        'subtitulo' => 'A união entre a fotografia artística e a dinâmica do vídeo moderno. Ideal para casamentos íntimos (60 convidados) que buscam impacto visual e compartilhamento imediato.',
        'beneficios' => [
            'Fotografia de Evento (8h): Cobertura focada na essência e na espontaneidade dos convidados.',
            'Sessão Engagement (Pré-Wedding): Ensaio de até 3h para conexão do casal com a lente antes do grande dia.',
            'Short Film de Cinema: Filme dinâmico (5 a 7 min) com os melhores momentos da cerimônia e recepção.',
            'Social Content (Story Maker): Entrega de conteúdo vertical pronto para redes sociais. Seus convidados acompanham os bastidores em tempo real.',
            'Making Of Completo: Registro da preparação da noiva e do noivo, capturando a expectativa e os detalhes.',
            'Bônus: Vídeo Save-the-Date incluso para o anúncio oficial.'
        ],
        'preco' => 4500.00,
        'condicoes' => '10% de desconto na entrada para contratos até 05/04/2026'
    ],
    [
        'nome' => 'Registro Essencial',
        'categoria' => 'wedding',
        'tipo' => 'plano',
        'subtitulo' => 'Um registro focado estritamente no protocolo, ideal para cerimônias curtas e objetivas que exigem um olhar profissional sobre os momentos principais.',
        'beneficios' => [
            'Fotografia de Cerimônia (4h): Cobertura pontual focada no protocolo religioso e fotos protocolares de família.',
            'Escopo Limitado: Plano focado em registros estáticos. Não inclui vídeo, drone, cobertura de preparativos ou ensaio externo.',
            'Entrega Digital: Acesso à galeria online exclusiva para download das fotos editadas.'
        ],
        'preco' => 2800.00,
        'condicoes' => ''
    ],
    // UPGRADES
    [
        'nome' => 'Boudoir da Noiva',
        'categoria' => 'wedding',
        'tipo' => 'servico',
        'subtitulo' => 'No dia do casamento',
        'beneficios' => [
            'Um ensaio de 1 h realizado após a maquiagem para registrar a beleza da noiva.'
        ],
        'preco' => 500.00,
        'condicoes' => ''
    ],
    [
        'nome' => 'Ensaio Pré-Wedding',
        'categoria' => 'wedding',
        'tipo' => 'servico',
        'subtitulo' => 'Conexão antes do grande dia',
        'beneficios' => [
            'Ensaio externo antes do casamento, incluindo pencard e 30 fotos reveladas.'
        ],
        'preco' => 1100.00,
        'condicoes' => ''
    ],
    [
        'nome' => 'Álbum Master (Família)',
        'categoria' => 'wedding',
        'tipo' => 'servico',
        'subtitulo' => 'Upgrade Família',
        'beneficios' => [
            'Adicione um Álbum Master para presentear ou guardar recordações extras.'
        ],
        'preco' => 950.00,
        'condicoes' => ''
    ]
];

try {
    foreach ($produtos as $p) {
        $id = gerarId();
        $stmt = $db->prepare('INSERT INTO servicos (id, nome, categoria, tipo, subtitulo, beneficios_json, preco_venda, condicoes_comerciais, markup, ativo) VALUES (?,?,?,?,?,?,?,?,?,1)');
        $stmt->execute([
            $id,
            $p['nome'],
            $p['categoria'],
            $p['tipo'],
            $p['subtitulo'],
            json_encode($p['beneficios']),
            $p['preco'],
            $p['condicoes'],
            30 // Markup padrão
        ]);
        echo "Cadastrado: {$p['nome']}\n";
    }
    echo "Sucesso! Todos os produtos foram cadastrados.\n";
} catch (Exception $e) {
    echo "Erro ao cadastrar: " . $e->getMessage() . "\n";
}
