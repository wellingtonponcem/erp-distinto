import { useState, useEffect } from 'react';
import { useRouter } from 'next/router';
import Link from 'next/link';

export default function RedefinirSenha() {
  const router = useRouter();
  const token = (router.query.token as string) || '';

  const [senha, setSenha] = useState('');
  const [confirmarSenha, setConfirmarSenha] = useState('');
  const [loading, setLoading] = useState(false);
  const [erro, setErro] = useState('');
  const [tokenValido, setTokenValido] = useState<boolean | null>(null);

  useEffect(() => {
    if (!router.isReady || !token) return;
    fetch(`/api/auth/verificar-token?token=${encodeURIComponent(token)}`)
      .then((r) => r.json())
      .then((d) => setTokenValido(!!d.valido))
      .catch(() => setTokenValido(false));
  }, [router.isReady, token]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErro('');

    if (senha.length < 8) {
      setErro('A nova senha deve ter pelo menos 8 caracteres.');
      return;
    }
    if (senha !== confirmarSenha) {
      setErro('As senhas não coincidem.');
      return;
    }

    setLoading(true);
    try {
      const res = await fetch('/api/auth/redefinir-senha', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, senha, confirmarSenha }),
      });
      const data = await res.json();
      if (res.ok && data.ok) {
        router.push('/?senha_redefinida=1');
      } else {
        setErro(data.erro || 'Não foi possível redefinir a senha.');
      }
    } catch {
      setErro('Erro de conexão. Verifique sua internet.');
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <div className="min-h-screen bg-[#050505] text-white flex flex-col items-center justify-center p-4 font-sans">
        <div className="max-w-md w-full bg-[#0c0c0c] border border-white/10 rounded-2xl shadow-2xl p-8 text-center">
          <h1 className="text-xl font-bold mb-3">Link inválido</h1>
          <p className="text-xs text-zinc-400 mb-6">Este link de redefinição é inválido ou está incompleto.</p>
          <Link href="/esqueci-senha" className="inline-block py-3 px-6 bg-[#c5a880] text-black rounded-xl font-extrabold text-sm">
            Solicitar novo link
          </Link>
          <div className="mt-6">
            <Link href="/" className="text-xs text-zinc-400 hover:text-[#c5a880]">← Voltar ao login</Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#050505] text-white flex flex-col items-center justify-center p-4 font-sans">
      <div className="max-w-md w-full bg-[#0c0c0c] border border-white/10 rounded-2xl shadow-2xl p-8">
        <div className="text-center mb-8">
          <div className="w-12 h-12 rounded-2xl bg-[#c5a880] text-black flex items-center justify-center font-black text-xl mx-auto mb-3 shadow-lg">
            D
          </div>
          <h1 className="text-2xl font-bold text-white tracking-tight">Nova senha</h1>
          <p className="text-xs text-zinc-400 mt-2">Crie uma nova senha com pelo menos 8 caracteres.</p>
        </div>

        {tokenValido === false && (
          <div className="p-3 bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-xl font-semibold text-center mb-4">
            Link inválido ou expirado. Solicite uma nova redefinição.
          </div>
        )}

        {erro && (
          <div className="p-3 bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-xl font-semibold text-center mb-4">
            {erro}
          </div>
        )}

        {tokenValido !== false && (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-zinc-400 uppercase mb-1">Nova senha</label>
              <input
                type="password"
                required
                value={senha}
                onChange={(e) => setSenha(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-black/60 border border-white/10 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#c5a880] focus:border-transparent outline-none transition"
                placeholder="••••••••"
                autoComplete="new-password"
                minLength={8}
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-zinc-400 uppercase mb-1">Confirmar nova senha</label>
              <input
                type="password"
                required
                value={confirmarSenha}
                onChange={(e) => setConfirmarSenha(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-black/60 border border-white/10 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#c5a880] focus:border-transparent outline-none transition"
                placeholder="••••••••"
                autoComplete="new-password"
                minLength={8}
              />
            </div>
            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 px-4 bg-[#c5a880] hover:bg-[#d4b78f] disabled:opacity-50 disabled:cursor-not-allowed text-black rounded-xl transition font-extrabold text-sm shadow-md mt-2"
            >
              {loading ? 'Redefinindo...' : 'Redefinir senha'}
            </button>
          </form>
        )}

        <div className="text-center mt-6 space-y-2">
          <Link href="/" className="block text-xs text-zinc-400 hover:text-[#c5a880]">Ir para o login</Link>
          {tokenValido === false && (
            <Link href="/esqueci-senha" className="block text-xs text-[#c5a880] font-semibold">Solicitar novo link</Link>
          )}
        </div>
      </div>
    </div>
  );
}
