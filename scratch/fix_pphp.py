#!/usr/bin/env python3
"""
Corrige p.php usando a versão original do git (p_original.php)
e substituindo a função mEnviar síncrona pela versão assíncrona com fetch.
"""

import re

original_path = r"c:\Users\Wellington\Documents\GitHub\erp-distinto\scratch\p_original.php"
output_path   = r"c:\Users\Wellington\Documents\GitHub\erp-distinto\p.php"

with open(original_path, "r", encoding="utf-8", errors="replace") as f:
    content = f.read()

# A função mEnviar original a ser substituída
old_func_marker = "        window.mEnviar = function () {"
new_func = """        window.mEnviar = async function () {
            if (!mSelected) return;
            const p = mPlanData[mSelected];
            let total = p.valor;
            let linhas = 'Plano: ' + p.nome + ' \u2014 ' + fmtBRL(p.valor);
            const extrasEnviados = [];
            if (mUpgrades.boudoir) {
                total += mUpgradeData.boudoir;
                linhas += '\\nUpgrade Boudoir \u2014 ' + fmtBRL(mUpgradeData.boudoir);
                extrasEnviados.push('boudoir_static');
            }
            if (mUpgrades.prewedding) {
                total += mUpgradeData.prewedding;
                linhas += '\\nUpgrade Pr\u00e9-Wedding \u2014 ' + fmtBRL(mUpgradeData.prewedding);
                extrasEnviados.push('prewedding_static');
            }

            const btnSend = document.getElementById('m-send-btn');
            const originalBtnText = btnSend ? btnSend.innerHTML : '';
            if (btnSend) {
                btnSend.disabled = true;
                btnSend.innerHTML = 'Gravando escolha...';
            }

            try {
                await fetch('<?= raizUrl(\\'/api/propostas/escolher-plano.php\\') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        slug: mSlug,
                        plano_id: mSelected,
                        extras: extrasEnviados,
                        condicoes: p.cond
                    })
                });
            } catch (error) {
                console.warn('N\u00e3o foi poss\u00edvel gravar a escolha automaticamente.', error);
            } finally {
                if (btnSend) {
                    btnSend.disabled = false;
                    btnSend.innerHTML = originalBtnText;
                }
            }

            const msg = 'Ol\u00e1! Somos ' + mNomeCasal + ' e gostar\u00edamos de confirmar nosso interesse na proposta da Distinto.\\n\\n' + linhas + '\\n\\nTotal: ' + fmtBRL(total) + '\\n\\nRef: ' + mSlug;
            window.open('https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(msg), '_blank');
        };"""

# Encontrar a função mEnviar original e substituí-la até o próximo '};'
# A estratégia é: encontrar o índice de início, encontrar o fechamento, e substituir
start_idx = content.find(old_func_marker)
if start_idx == -1:
    print("ERRO: Não encontrou a função mEnviar original!")
    exit(1)

# Encontrar o fechamento '};' após o início
# A função vai até o primeiro '};' após o início
close_marker = "        };"
close_idx = content.find(close_marker, start_idx)
if close_idx == -1:
    print("ERRO: Não encontrou o fechamento '};' da função mEnviar!")
    exit(1)

end_idx = close_idx + len(close_marker)

print(f"Encontrou mEnviar no índice {start_idx}")
print(f"Fechamento no índice {end_idx}")
print(f"Trecho a substituir: {repr(content[start_idx:start_idx+50])}...")

new_content = content[:start_idx] + new_func + content[end_idx:]

# Também precisamos adicionar window.openInteractiveModal que chama openPlanModal
# O original usa openInteractiveModal mas define openPlanModal
# Verificar se já existe
if "window.openInteractiveModal" not in new_content and "window.openPlanModal" in new_content:
    # Adicionar alias após a definição de openPlanModal
    alias = "\n        window.openInteractiveModal = window.openPlanModal;"
    insert_after = "        };\n        window.closePlanModal"
    new_content = new_content.replace(insert_after, "        };" + alias + "\n        window.closePlanModal", 1)
    print("Adicionado alias openInteractiveModal -> openPlanModal")

with open(output_path, "w", encoding="utf-8") as f:
    f.write(new_content)

print(f"\nArquivo gerado com sucesso: {output_path}")
print(f"Linhas: {len(new_content.splitlines())}")
print(f"Tamanho: {len(new_content)} bytes")
