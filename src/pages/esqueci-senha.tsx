import { useState } from 'react';
import Link from 'next/link';

export default function EsqueciSenha() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [mensagem, setMensagem] = useState('');
  const [erro, setErro] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErro('');
    setMensagem('');

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setErro('Informe um e-mail válido.');
      return;
    }

    setLoading(true);
    try {
      const res = await fetch('/api/auth/solicitar-reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email.trim().toLowerCase() }),
      });
      const data = await res.json();
      if (res.ok && data.ok) {
        setMensagem(data.mensagem || 'Se o e-mail estiver cadastrado, enviaremos um link de redefinição em alguns minutos.');
      } else {
        setErro(data.erro || 'Não foi possível iniciar a recuperação. Tente novamente.');
      }
    } catch {
      setErro('Erro de conexão. Verifique sua internet.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#050505] text-white flex flex-col items-center justify-center p-4 font-sans">
      <div className="max-w-md w-full bg-[#0c0c0c] border border-white/10 rounded-2xl shadow-2xl p-8">
        <div className="text-center mb-8">
          <div className="w-12 h-12 rounded-2xl bg-[#c5a880] text-black flex items-center justify-center font-black text-xl mx-auto mb-3 shadow-lg">
            D
          </div>
          <h1 className="text-2xl font-bold text-white tracking-tight">Recuperar senha</h1>
          <p className="text-xs text-zinc-400 mt-2 leading-relaxed">
            Digite seu e-mail. Se ele estiver cadastrado, enviaremos um link seguro para criar uma nova senha.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          {mensagem && (
            <div className="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs rounded-xl font-medium text-center">
              {mensagem}
            </div>
          )}
          {erro && (
            <div className="p-3 bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-xl font-semibold text-center">
              {erro}
            </div>
          )}

          <div>
            <label className="block text-xs font-semibold text-zinc-400 uppercase mb-1">E-mail</label>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-black/60 border border-white/10 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#c5a880] focus:border-transparent outline-none transition"
              placeholder="seu@email.com"
              autoComplete="email"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3 px-4 bg-[#c5a880] hover:bg-[#d4b78f] disabled:opacity-50 disabled:cursor-not-allowed text-black rounded-xl transition font-extrabold text-sm shadow-md mt-2"
          >
            {loading ? 'Enviando...' : 'Enviar link de redefinição'}
          </button>
        </form>

        <div className="text-center mt-6">
          <Link href="/" className="text-xs text-zinc-400 hover:text-[#c5a880] transition">
            ← Voltar ao login
          </Link>
        </div>
      </div>
    </div>
  );
}
