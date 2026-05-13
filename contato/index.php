<?php
require_once dirname(__DIR__) . '/includes/db.php';
$page_title = 'Contato';
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $type    = trim($_POST['project_type'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, preencha seu nome e um e-mail válido.';
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO contact_messages (name, email, project_type, message, created_at)
                 VALUES (:name, :email, :type, :message, NOW())'
            );
            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':type'    => $type,
                ':message' => $message,
            ]);
            $success = true;
        } catch (PDOException $e) {
            $error = 'Ocorreu um erro ao enviar. Tente novamente.';
        }
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>
<main class="relative pt-48 pb-40">

    <section class="px-[10%] mb-32">
        <h1 class="font-headline font-black text-[8vw] md:text-[7vw] leading-[0.9] uppercase tracking-tighter mb-12 mix-blend-difference">
            TODO PROJETO CARREGA UMA HISTÓRIA.<br/>
            <span class="text-outline">A QUESTÃO É COMO ELA VAI SER CONTADA.</span>
        </h1>
        <p class="font-serif italic text-2xl md:text-4xl text-white/60 max-w-2xl">
            Seja uma marca ou um momento único, nós transformamos em narrativa.
        </p>
    </section>

    <section class="px-[10%] grid grid-cols-12 gap-12 lg:gap-24">
        <div class="col-span-12 lg:col-span-5 mb-20 lg:mb-0">
            <div class="sticky top-40 space-y-12 border-l border-white/10 pl-12">
                <div class="space-y-6">
                    <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-white/30">Process / Intent</p>
                    <p class="font-serif text-3xl leading-tight text-white/90">
                        Trabalhamos com projetos que exigem mais do que execução.<br/>
                        Exigem olhar, direção e intenção.
                    </p>
                </div>
                <p class="font-headline font-bold text-xl uppercase tracking-tight text-white/40 leading-snug">
                    Se você busca apenas alguém para registrar ou produzir, <br/>
                    talvez não sejamos o caminho.
                </p>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-7">
            <h2 class="font-headline font-black text-4xl mb-20 uppercase tracking-tighter">
                ME CONTA SOBRE O SEU PROJETO
            </h2>

            <?php if ($success): ?>
                <div class="border border-white/20 p-12 mb-12 text-center">
                    <p class="font-serif italic text-2xl text-white/80 mb-4">Mensagem recebida.</p>
                    <p class="font-mono text-xs uppercase tracking-widest text-white/40">Entraremos em contato em breve.</p>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="border border-red-500/30 bg-red-500/10 p-6 mb-12">
                        <p class="font-mono text-xs text-red-400"><?php echo h($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo url('contato'); ?>" class="space-y-16">
                    <div class="group">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-white/30 block mb-4">Nome completo</label>
                        <input name="name" value="<?php echo h($_POST['name'] ?? ''); ?>"
                               class="w-full bg-transparent border-t-0 border-x-0 border-b border-white/10 focus:border-white focus:ring-0 px-0 py-6 transition-all text-2xl placeholder:text-white/5 outline-none font-headline"
                               placeholder="COMO PODEMOS TE CHAMAR?" type="text"/>
                    </div>

                    <div class="group">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-white/30 block mb-4">E-mail</label>
                        <input name="email" value="<?php echo h($_POST['email'] ?? ''); ?>"
                               class="w-full bg-transparent border-t-0 border-x-0 border-b border-white/10 focus:border-white focus:ring-0 px-0 py-6 transition-all text-2xl placeholder:text-white/5 outline-none font-headline"
                               placeholder="HELLO@DOMAIN.COM" type="email"/>
                    </div>

                    <div class="space-y-6">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-white/30 block mb-4">Tipo de projeto</label>
                        <div class="flex flex-wrap gap-4">
                            <?php foreach (['Marca / Empresa', 'Evento', 'Casamento'] as $type): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="project_type" value="<?php echo h($type); ?>"
                                       class="sr-only peer"
                                       <?php echo (($_POST['project_type'] ?? '') === $type) ? 'checked' : ''; ?>>
                                <span class="px-8 py-3 rounded-full border border-white/10 text-white/40 peer-checked:bg-white peer-checked:text-black peer-checked:border-white font-mono text-[10px] uppercase tracking-widest transition-all hover:border-white/40">
                                    <?php echo h($type); ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="group">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-white/30 block mb-4">O que você quer que esse projeto represente?</label>
                        <textarea name="message" rows="4"
                                  class="w-full bg-transparent border-t-0 border-x-0 border-b border-white/10 focus:border-white focus:ring-0 px-0 py-6 transition-all text-2xl placeholder:text-white/5 outline-none font-serif italic min-h-[150px] resize-none"
                                  placeholder="Fale um pouco sobre a alma do projeto..."><?php echo h($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <div class="pt-12 border-t border-white/5">
                        <p class="font-serif italic text-xl text-white/40 mb-12 max-w-md">
                            Cada projeto que assumimos carrega intenção.<br/>
                            Por isso, escolhemos com cuidado com quem caminhamos.
                        </p>
                        <button type="submit" class="btn-cta-wipe group flex items-center gap-8 px-12 py-6 font-headline font-black text-xs uppercase tracking-[0.3em] border-2 border-white/20 hover:border-white transition-all duration-500">
                            ENVIAR PROPOSTA
                            <span class="arrow transition-transform">></span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
