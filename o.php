<?php
/**
 * Visualizador Público de Orçamentos
 * wedistinto.com/o/[slug] ou wedistinto.com/orcamento/[slug]
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die("Orçamento não encontrado.");
}

$db = Database::get();

// Se a tabela ainda não tiver dados por algum motivo, executa o auto-seed
try {
    $countCheck = $db->query("SELECT COUNT(*) FROM orcamentos")->fetchColumn();
    if ($countCheck == 0 && file_exists(__DIR__ . '/setup/migration_orcamentos.php')) {
        include_once __DIR__ . '/setup/migration_orcamentos.php';
    }
} catch (Exception $e) {}

$stmt = $db->prepare("SELECT * FROM orcamentos WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$orcamento = $stmt->fetch();

if (!$orcamento) {
    die("Orçamento não encontrado ou expirado.");
}

$dados = json_decode($orcamento['dados_json'], true) ?? [];
$cliente = $orcamento['cliente_nome'];
$titulo = $orcamento['titulo'];
$subtitulo = $orcamento['subtitulo'] ?? '';
$tipo = $orcamento['tipo'];
$status = $orcamento['status'];

$validadeFormatada = !empty($orcamento['validade']) ? date('d/m/Y', strtotime($orcamento['validade'])) : null;
$hoje = date('Y-m-d');
$vencido = (!empty($orcamento['validade']) && $orcamento['validade'] < $hoje);

// Dados estruturados do orçamento
$configGeral = $dados['configuracao_geral'] ?? [];
$colecoes = $dados['colecao_albuns'] ?? [];
$galeriaAcabamentos = $dados['galeria_acabamentos'] ?? [];

// Configurações da Empresa para Rodapé
$configEmpresa = [];
try {
    $configEmpresa = $db->query("SELECT * FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch() ?: [];
} catch (Exception $e) {}

$whatsappEmpresa = preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '5527999999999');
if (!str_starts_with($whatsappEmpresa, '55') && strlen($whatsappEmpresa) >= 10) {
    $whatsappEmpresa = '55' . $whatsappEmpresa;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> — <?= htmlspecialchars($cliente) ?></title>
    
    <!-- Google Fonts & Lucide Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            purple: '#7c3aed',
                            purpleDark: '#5b21b6',
                            gold: '#d97706',
                            goldLight: '#f59e0b',
                            accent: '#a855f7'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif'],
                        cinzel: ['Cinzel', 'serif']
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #09090b;
            color: #f4f4f5;
            font-family: 'Inter', sans-serif;
            background-image: 
                radial-gradient(circle at 20% 15%, rgba(124, 58, 237, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 80% 60%, rgba(217, 119, 6, 0.08) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .glass-panel {
            background: rgba(24, 24, 27, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-card {
            background: rgba(39, 39, 42, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            border-color: rgba(168, 85, 247, 0.4);
            box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.25);
            transform: translateY(-2px);
        }

        .glass-card.selected {
            border-color: #a855f7;
            background: rgba(124, 58, 237, 0.12);
            box-shadow: 0 0 30px rgba(168, 85, 247, 0.3);
        }

        .gold-glow {
            box-shadow: 0 0 25px rgba(217, 119, 6, 0.25);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #09090b; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3f3f46; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .glass-panel, .glass-card { background: white !important; border: 1px solid #ddd !important; color: black !important; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased pb-48 sm:pb-36">

    <!-- Top Brand Nav -->
    <header class="w-full glass-panel sticky top-0 z-40 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
            <div class="flex items-center space-x-2.5 sm:space-x-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-brand-purple to-purple-900 flex items-center justify-center font-bold text-white shadow-lg shadow-purple-900/40 text-sm sm:text-base">
                    D
                </div>
                <div>
                    <h1 class="font-heading font-extrabold tracking-wider text-base sm:text-lg text-white">DISTINTO</h1>
                    <p class="text-[9px] sm:text-[10px] tracking-widest uppercase text-zinc-400 font-semibold hidden sm:block">Propostas & Orçamentos Premium</p>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-3 no-print">
                <button onclick="window.print()" class="hidden sm:inline-flex items-center space-x-2 px-3 py-2 rounded-lg bg-zinc-800/80 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition-colors">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Imprimir / PDF</span>
                </button>
                <button onclick="copiarLink()" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-lg bg-zinc-800/80 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition-colors">
                    <i data-lucide="share-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                    <span id="txt-share">Compartilhar</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-8 flex-1 w-full space-y-6 sm:space-y-10">

        <!-- Hero Section -->
        <section class="glass-panel p-5 sm:p-12 rounded-2xl sm:rounded-3xl relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-12 -mr-12 w-64 h-64 bg-brand-purple/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5 sm:gap-6">
                <div class="space-y-2.5 sm:space-y-3 max-w-3xl">
                    <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-300 text-[10px] sm:text-xs font-bold uppercase tracking-wider">
                        <i data-lucide="sparkles" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
                        <span>Orçamento Comercial</span>
                    </div>

                    <h2 class="text-2xl sm:text-5xl font-heading font-extrabold text-white tracking-tight leading-tight">
                        <?= htmlspecialchars($titulo) ?>
                    </h2>

                    <p class="text-sm sm:text-lg text-purple-200/80 font-medium">
                        <?= htmlspecialchars($subtitulo) ?>
                    </p>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 pt-1 sm:pt-2 text-xs text-zinc-400">
                        <span class="flex items-center space-x-1.5 bg-zinc-900/60 px-2.5 py-1.5 rounded-lg border border-white/5">
                            <i data-lucide="user" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-purple-400"></i>
                            <span class="text-zinc-200 font-semibold text-xs"><?= htmlspecialchars($cliente) ?></span>
                        </span>
                        
                        <?php if ($validadeFormatada): ?>
                        <span class="flex items-center space-x-1.5 bg-zinc-900/60 px-2.5 py-1.5 rounded-lg border border-white/5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 sm:w-4 sm:h-4 <?= $vencido ? 'text-rose-400' : 'text-emerald-400' ?>"></i>
                            <span class="text-xs">Validade: <strong class="<?= $vencido ? 'text-rose-300' : 'text-zinc-200' ?>"><?= $validadeFormatada ?></strong></span>
                        </span>
                        <?php endif; ?>

                        <span class="flex items-center space-x-1.5 bg-zinc-900/60 px-2.5 py-1.5 rounded-lg border border-white/5">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-400"></i>
                            <span class="text-xs">Status: <strong class="uppercase text-amber-300"><?= htmlspecialchars($status) ?></strong></span>
                        </span>
                    </div>
                </div>

                <!-- Quick Action Card -->
                <div class="glass-card p-4 sm:p-6 rounded-2xl flex flex-col items-center justify-center text-center space-y-2.5 sm:space-y-3 min-w-full sm:min-w-[240px] border-purple-500/20">
                    <span class="text-[10px] sm:text-xs uppercase font-bold tracking-widest text-zinc-400">Investimento A Partir De</span>
                    <div class="text-2xl sm:text-4xl font-heading font-extrabold text-white tracking-tight">
                        R$ <?= number_format($orcamento['valor_total'] ?? 1250, 2, ',', '.') ?>
                    </div>
                    <p class="text-[10px] sm:text-[11px] text-zinc-400">Em até 6x sem juros ou desconto à vista</p>
                    <a href="#colecoes-section" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-brand-purple to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-purple-900/30 text-center">
                        Ver Coleções
                    </a>
                </div>
            </div>
        </section>

        <!-- General Specifications (`configuracao_geral`) -->
        <?php if (!empty($configGeral)): ?>
        <section class="space-y-4">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-heading font-bold text-white tracking-wide">Especificações Técnicas da Linha</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                <!-- Spec 1: Tamanhos -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                    <div class="p-2.5 sm:p-3 rounded-xl bg-purple-500/10 text-purple-400 shrink-0">
                        <i data-lucide="maximize-2" class="w-4.5 h-4.5 sm:w-5 sm:h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Dimensões</span>
                        <h4 class="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5"><?= htmlspecialchars($configGeral['tamanho_fechado'] ?? '30x30 cm') ?> (Fechado)</h4>
                        <p class="text-[11px] sm:text-xs text-zinc-400"><?= htmlspecialchars($configGeral['tamanho_aberto'] ?? '30x60 cm') ?> panorâmico</p>
                    </div>
                </div>

                <!-- Spec 2: Abertura e Papel -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                    <div class="p-2.5 sm:p-3 rounded-xl bg-amber-500/10 text-amber-400 shrink-0">
                        <i data-lucide="book-open" class="w-4.5 h-4.5 sm:w-5 sm:h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Encadernação</span>
                        <h4 class="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5">Panorâmica 180°</h4>
                        <p class="text-[11px] sm:text-xs text-zinc-400">Lâminas rígidas de 800g</p>
                    </div>
                </div>

                <!-- Spec 3: Páginas Base -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                    <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-500/10 text-emerald-400 shrink-0">
                        <i data-lucide="file-text" class="w-4.5 h-4.5 sm:w-5 sm:h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Capacidade Base</span>
                        <h4 class="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5"><?= htmlspecialchars($configGeral['paginas_base'] ?? 20) ?> Páginas (10 Lâminas)</h4>
                        <p class="text-[11px] sm:text-xs text-zinc-400">Expansível com extras</p>
                    </div>
                </div>

                <!-- Spec 4: Retirada / Entrega -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                    <div class="p-2.5 sm:p-3 rounded-xl bg-blue-500/10 text-blue-400 shrink-0">
                        <i data-lucide="package-check" class="w-4.5 h-4.5 sm:w-5 sm:h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Entrega</span>
                        <h4 class="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5"><?= htmlspecialchars($configGeral['retirada'] ?? 'Presencial') ?></h4>
                        <p class="text-[11px] sm:text-xs text-zinc-400">Com embalagem especial</p>
                    </div>
                </div>
            </div>

            <!-- Servicos Inclusos -->
            <?php if (!empty($configGeral['servicos_inclusos'])): ?>
            <div class="glass-panel p-4 sm:p-6 rounded-2xl border border-white/5 mt-3 sm:mt-4">
                <span class="text-[10px] sm:text-xs uppercase font-bold tracking-widest text-purple-400 block mb-2.5">Serviços Premium Inclusos no Orçamento</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                    <?php foreach ($configGeral['servicos_inclusos'] as $servico): ?>
                        <div class="flex items-center space-x-2 text-xs font-semibold text-zinc-200 bg-zinc-900/50 p-2.5 rounded-xl border border-white/5">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span><?= htmlspecialchars($servico) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Collections Showcase (`colecao_albuns`) -->
        <section id="colecoes-section" class="space-y-4 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <div class="flex items-center space-x-2.5 sm:space-x-3">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="crown" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-heading font-extrabold text-white tracking-wide">Opções de Coleção & Acabamentos</h3>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1">Selecione a coleção ideal para visualizar os detalhes completos e simular o investimento.</p>
                </div>

                <!-- Interactive Extra Sheet Selector -->
                <div class="glass-panel p-2.5 sm:p-3 rounded-2xl flex items-center justify-between sm:justify-start space-x-2 sm:space-x-3 w-full sm:w-auto border border-purple-500/30">
                    <span class="text-xs font-bold text-zinc-300 pl-1">Lâminas Extras:</span>
                    <select id="select-laminas-extras" onchange="recalcularTotais()" class="bg-zinc-900 text-purple-300 font-bold text-xs rounded-xl px-2.5 py-1.5 border border-purple-500/30 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="0">+0 Lâminas (20 Páginas padrão)</option>
                        <option value="2">+2 Lâminas (24 Páginas)</option>
                        <option value="4">+4 Lâminas (28 Páginas)</option>
                        <option value="6">+6 Lâminas (32 Páginas)</option>
                        <option value="8">+8 Lâminas (36 Páginas)</option>
                        <option value="10">+10 Lâminas (40 Páginas)</option>
                    </select>
                </div>
            </div>

            <!-- Collections Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <?php foreach ($colecoes as $index => $colecao): 
                    $isTop = ($colecao['categoria_original'] ?? '') === 'Top Master' || ($index === 2);
                    $isMid = ($colecao['categoria_original'] ?? '') === 'Intermediário' || ($index === 1);
                ?>
                <div id="card-colecao-<?= $colecao['id'] ?>" 
                     onclick="selecionarColecao('<?= $colecao['id'] ?>', '<?= htmlspecialchars($colecao['nome_comercial']) ?>', <?= $colecao['investimento_cliente'] ?>, <?= $colecao['valor_lamina_extra'] ?>)"
                     class="glass-card rounded-3xl p-6 flex flex-col justify-between cursor-pointer relative overflow-hidden group border-2 <?= $index === 0 ? 'selected' : '' ?>">

                    <?php if ($isTop): ?>
                    <div class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 bg-gradient-to-r from-amber-500 to-yellow-400 text-zinc-950 font-black text-[10px] uppercase tracking-widest px-3 py-1 rounded-full shadow-xl">
                        TOP MASTER LUX
                    </div>
                    <?php elseif ($isMid): ?>
                    <div class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 bg-purple-950/90 text-purple-300 border border-purple-500/50 font-bold text-[10px] uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-md shadow-lg">
                        MAIS PROCURADO
                    </div>
                    <?php endif; ?>

                    <div class="space-y-5">
                        <!-- Product Preview Image if available -->
                        <?php if (!empty($colecao['estojo']['imagem_referencia'])): ?>
                        <div class="w-full h-48 rounded-2xl overflow-hidden bg-zinc-900 relative group-hover:scale-[1.02] transition-transform">
                            <img src="<?= htmlspecialchars($colecao['estojo']['imagem_referencia']) ?>" 
                                 alt="<?= htmlspecialchars($colecao['nome_comercial']) ?>" 
                                 class="w-full h-full object-cover object-center opacity-90 group-hover:opacity-100 transition-opacity"
                                 onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent opacity-80"></div>
                        </div>
                        <?php endif; ?>

                        <!-- Title & Description -->
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-purple-400 block mb-1">
                                <?= htmlspecialchars($colecao['categoria_original'] ?? 'Coleção Premium') ?>
                            </span>
                            <h4 class="text-2xl font-heading font-extrabold text-white tracking-tight">
                                <?= htmlspecialchars($colecao['nome_comercial']) ?>
                            </h4>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                                <?= htmlspecialchars($colecao['descricao']) ?>
                            </p>
                        </div>

                        <!-- Technical Finishes Checklist -->
                        <?php if (!empty($colecao['acabamento_detalhado'])): ?>
                        <div class="space-y-2 pt-2 border-t border-white/5 text-xs">
                            <span class="text-[10px] font-bold uppercase text-zinc-400 block tracking-wider">Acabamento do Álbum:</span>
                            <?php foreach ($colecao['acabamento_detalhado'] as $itemKey => $itemVal): ?>
                                <div class="flex items-start space-x-2 text-zinc-300">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-purple-400 mt-0.5 shrink-0"></i>
                                    <span><strong class="capitalize text-zinc-200"><?= $itemKey ?>:</strong> <?= htmlspecialchars($itemVal) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Estojo / Case -->
                        <?php if (!empty($colecao['estojo'])): ?>
                        <div class="bg-zinc-900/80 p-3.5 rounded-2xl border border-white/5 space-y-1">
                            <div class="flex items-center space-x-2 text-xs font-bold text-amber-300">
                                <i data-lucide="box" class="w-4 h-4"></i>
                                <span><?= htmlspecialchars($colecao['estojo']['tipo'] ?? 'Estojo Nobre') ?></span>
                            </div>
                            <p class="text-[11px] text-zinc-400 leading-tight">
                                <?= htmlspecialchars($colecao['estojo']['descricao'] ?? '') ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Price & Selection Indicator -->
                    <div class="pt-6 mt-6 border-t border-white/10 flex flex-col space-y-3">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-zinc-400 font-medium">Investimento:</span>
                            <div class="text-right">
                                <div class="text-2xl font-heading font-extrabold text-white tracking-tight" id="preco-exibicao-<?= $colecao['id'] ?>">
                                    R$ <?= number_format($colecao['investimento_cliente'], 2, ',', '.') ?>
                                </div>
                                <span class="text-[10px] text-purple-300 font-semibold block">
                                    + R$ <?= number_format($colecao['valor_lamina_extra'], 2, ',', '.') ?> / lâmina extra
                                </span>
                            </div>
                        </div>

                        <button type="button" class="btn-selecionar w-full py-3 rounded-xl bg-zinc-800 hover:bg-purple-600 text-white font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center space-x-2 group-hover:bg-purple-600">
                            <i data-lucide="circle-dot" class="w-4 h-4"></i>
                            <span>Selecionar Coleção</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Finishes Showcase Gallery (`galeria_acabamentos`) -->
        <?php if (!empty($galeriaAcabamentos)): ?>
        <section class="space-y-6 pt-6">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">
                    <i data-lucide="image" class="w-4 h-4"></i>
                </div>
                <h3 class="text-2xl font-heading font-extrabold text-white tracking-wide">Galeria de Detalhes & Acabamentos</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($galeriaAcabamentos as $galeriaItem): ?>
                <div class="glass-card rounded-2xl overflow-hidden group">
                    <div class="h-48 bg-zinc-900 relative overflow-hidden">
                        <img src="<?= htmlspecialchars($galeriaItem['imagem_exemplo']) ?>" 
                             alt="<?= htmlspecialchars($galeriaItem['item']) ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                             onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent opacity-80"></div>
                    </div>
                    <div class="p-5 space-y-1">
                        <h4 class="font-heading font-bold text-base text-white"><?= htmlspecialchars($galeriaItem['item']) ?></h4>
                        <p class="text-xs text-zinc-400 leading-relaxed"><?= htmlspecialchars($galeriaItem['descricao']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <!-- Floating Footer Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 z-50 glass-panel border-t border-purple-500/30 px-3 py-2.5 sm:p-5 no-print shadow-2xl backdrop-blur-2xl" style="padding-bottom: max(0.6rem, env(safe-area-inset-bottom));">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-1.5 sm:gap-4">
            
            <!-- Header do rodapé no mobile: Coleção Selecionada -->
            <div class="flex items-center justify-between sm:justify-start space-x-3">
                <div class="hidden sm:flex w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/40 items-center justify-center text-purple-400 shrink-0">
                    <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center space-x-1.5 sm:block">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-purple-400 sm:text-zinc-400">Coleção Selecionada:</span>
                    <h5 id="footer-colecao-nome" class="font-heading font-extrabold text-white text-xs sm:text-base truncate max-w-[200px] sm:max-w-none">
                        <?= !empty($colecoes[0]['nome_comercial']) ? htmlspecialchars($colecoes[0]['nome_comercial']) : 'Coleção Essencial' ?>
                    </h5>
                </div>
            </div>

            <!-- Linha principal com preço e botões no mobile -->
            <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-between sm:justify-end">
                <div class="text-left sm:text-right">
                    <span class="text-[8px] sm:text-[10px] uppercase font-bold tracking-wider text-zinc-400 block">Investimento Total</span>
                    <div id="footer-preco-total" class="text-lg sm:text-3xl font-heading font-black text-white tracking-tight leading-tight">
                        R$ <?= number_format($colecoes[0]['investimento_cliente'] ?? 1250, 2, ',', '.') ?>
                    </div>
                </div>

                <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
                    <a id="btn-whatsapp-direto" 
                       href="https://wa.me/<?= $whatsappEmpresa ?>?text=<?= urlencode("Olá! Gostaria de conversar sobre o Orçamento: " . $titulo . " (" . $cliente . ")") ?>" 
                       target="_blank" 
                       title="Falar no WhatsApp"
                       class="p-2.5 sm:px-4 sm:py-3.5 rounded-xl sm:rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center space-x-1.5 shadow-lg shadow-emerald-900/30">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span class="hidden md:inline">WhatsApp</span>
                    </a>

                    <button onclick="abrirModalAprovacao()" class="px-3.5 py-2.5 sm:px-6 sm:py-3.5 rounded-xl sm:rounded-2xl bg-gradient-to-r from-brand-purple to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-xl shadow-purple-900/50 flex items-center space-x-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 sm:w-4.5 sm:h-4.5"></i>
                        <span>Aprovar Orçamento</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Aprovação -->
    <div id="modal-aprovacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md hidden p-4">
        <div class="glass-panel w-full max-w-lg rounded-3xl p-6 sm:p-8 space-y-6 border border-purple-500/40 relative animate-in fade-in zoom-in duration-200">
            <button onclick="fecharModalAprovacao()" class="absolute top-5 right-5 text-zinc-400 hover:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>

            <div class="space-y-2">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold uppercase">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Confirmar Escolha</span>
                </div>
                <h3 class="text-2xl font-heading font-extrabold text-white">Aprovação do Orçamento</h3>
                <p class="text-xs text-zinc-400">Preencha seus dados de contato para finalizarmos a confirmação da sua coleção.</p>
            </div>

            <form id="form-aprovacao" onsubmit="enviarAprovacao(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Nome Completo</label>
                    <input type="text" id="ap-nome" required value="<?= htmlspecialchars($cliente) ?>" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">WhatsApp / Telefone</label>
                    <input type="text" id="ap-telefone" required placeholder="(27) 99999-9999" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-500">
                </div>

                <div class="bg-zinc-900/90 p-4 rounded-2xl border border-white/5 space-y-2">
                    <div class="flex justify-between text-xs text-zinc-400">
                        <span>Coleção Escolhida:</span>
                        <strong id="modal-colecao-nome" class="text-purple-300 font-bold">--</strong>
                    </div>
                    <div class="flex justify-between text-xs text-zinc-400">
                        <span>Lâminas Extras:</span>
                        <strong id="modal-laminas-qtd" class="text-zinc-200 font-bold">+0 Lâminas</strong>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-white pt-2 border-t border-white/10">
                        <span>Investimento Final:</span>
                        <strong id="modal-investimento-total" class="text-emerald-400 text-lg">R$ 0,00</strong>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Observações ou Preferências de Acabamento</label>
                    <textarea id="ap-obs" rows="3" placeholder="Ex: Preferência por capa em couro sintético lilás..." class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"></textarea>
                </div>

                <button type="submit" id="btn-submit-aprovacao" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-lg shadow-emerald-900/40">
                    Confirmar & Enviar Aprovação
                </button>
            </form>
        </div>
    </div>

    <!-- JavaScript para Interatividade -->
    <script>
        lucide.createIcons();

        // Dados JS das coleções
        const colecoesDados = <?= json_encode($colecoes) ?>;
        let colecaoSelecionada = colecoesDados[0] || { id: 'default', nome_comercial: 'Coleção Base', investimento_cliente: 1250, valor_lamina_extra: 35 };

        function selecionarColecao(id, nome, basePrice, extraPrice) {
            const achou = colecoesDados.find(c => c.id === id);
            if (achou) {
                colecaoSelecionada = achou;
            } else {
                colecaoSelecionada = { id, nome_comercial: nome, investimento_cliente: basePrice, valor_lamina_extra: extraPrice };
            }

            // Atualiza bordas selecionadas
            document.querySelectorAll('#colecoes-section .glass-card').forEach(card => {
                card.classList.remove('selected');
            });
            const elCard = document.getElementById('card-colecao-' + id);
            if (elCard) elCard.classList.add('selected');

            recalcularTotais();
        }

        function recalcularTotais() {
            const laminasExtras = parseInt(document.getElementById('select-laminas-extras').value) || 0;

            // Recalcular preço em todos os cards
            colecoesDados.forEach(c => {
                const totalColecao = parseFloat(c.investimento_cliente) + (laminasExtras * parseFloat(c.valor_lamina_extra));
                const elPreco = document.getElementById('preco-exibicao-' + c.id);
                if (elPreco) {
                    elPreco.textContent = 'R$ ' + totalColecao.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            });

            // Preço total da coleção selecionada
            const basePrice = parseFloat(colecaoSelecionada.investimento_cliente);
            const extraPrice = parseFloat(colecaoSelecionada.valor_lamina_extra);
            const precoFinal = basePrice + (laminasExtras * extraPrice);

            // Atualizar Footer
            document.getElementById('footer-colecao-nome').textContent = colecaoSelecionada.nome_comercial;
            document.getElementById('footer-preco-total').textContent = 'R$ ' + precoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Atualizar Modal
            document.getElementById('modal-colecao-nome').textContent = colecaoSelecionada.nome_comercial;
            document.getElementById('modal-laminas-qtd').textContent = '+' + laminasExtras + ' Lâminas (' + (20 + (laminasExtras * 2)) + ' Páginas)';
            document.getElementById('modal-investimento-total').textContent = 'R$ ' + precoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Atualizar Link WhatsApp Direto
            const msgWhats = `Olá! Tenho interesse no Orçamento: <?= htmlspecialchars($titulo) ?>. Escolhi a ${colecaoSelecionada.nome_comercial} (+${laminasExtras} lâminas extras, Total: R$ ${precoFinal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}). Podem me ajudar?`;
            document.getElementById('btn-whatsapp-direto').href = `https://wa.me/<?= $whatsappEmpresa ?>?text=${encodeURIComponent(msgWhats)}`;
        }

        function abrirModalAprovacao() {
            document.getElementById('modal-aprovacao').classList.remove('hidden');
        }

        function fecharModalAprovacao() {
            document.getElementById('modal-aprovacao').classList.add('hidden');
        }

        function copiarLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const txt = document.getElementById('txt-share');
                txt.textContent = 'Link Copiado!';
                setTimeout(() => txt.textContent = 'Compartilhar', 3000);
            });
        }

        async function enviarAprovacao(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-aprovacao');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            const laminasExtras = parseInt(document.getElementById('select-laminas-extras').value) || 0;
            const basePrice = parseFloat(colecaoSelecionada.investimento_cliente);
            const extraPrice = parseFloat(colecaoSelecionada.valor_lamina_extra);
            const precoFinal = basePrice + (laminasExtras * extraPrice);

            const payload = {
                slug: '<?= $slug ?>',
                cliente_nome: document.getElementById('ap-nome').value,
                telefone: document.getElementById('ap-telefone').value,
                colecao_id: colecaoSelecionada.id,
                colecao_nome: colecaoSelecionada.nome_comercial,
                laminas_extras: laminasExtras,
                valor_total: precoFinal,
                observacoes: document.getElementById('ap-obs').value
            };

            try {
                const resp = await fetch('<?= raizUrl('/api/orcamentos/aprovar.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await resp.json();

                if (data.success) {
                    alert('Orçamento aprovado com sucesso! Redirecionando para contato no WhatsApp...');
                    window.location.href = data.whatsapp_url || document.getElementById('btn-whatsapp-direto').href;
                } else {
                    alert(data.erro || 'Falha ao registrar aprovação.');
                    btn.disabled = false;
                    btn.textContent = 'Confirmar & Enviar Aprovação';
                }
            } catch (err) {
                alert('Erro ao conectar ao servidor. Você será direcionado ao WhatsApp da agência.');
                window.location.href = document.getElementById('btn-whatsapp-direto').href;
            }
        }

        // Inicializar com a primeira coleção
        if (colecoesDados.length > 0) {
            selecionarColecao(colecoesDados[0].id, colecoesDados[0].nome_comercial, colecoesDados[0].investimento_cliente, colecoesDados[0].valor_lamina_extra);
        }
    </script>
</body>
</html>
