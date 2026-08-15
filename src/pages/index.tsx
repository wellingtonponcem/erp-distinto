import { useEffect, useState } from 'react';

export default function Home() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [email, setEmail] = useState('');
  const [senha, setSenha] = useState('');
  const [erro, setErro] = useState('');

  useEffect(() => {
    fetch('/api/auth/me')
      .then((res) => res.json())
      .then((data) => {
        if (data.ok && data.user) {
          setUser(data.user);
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErro('');

    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, senha }),
      });
      const data = await res.json();

      if (res.ok && data.ok) {
        setUser(data.user);
      } else {
        setErro(data.erro || 'Falha no login');
      }
    } catch (err: any) {
      setErro('Erro de conexão com o servidor');
    }
  };

  const handleLogout = async () => {
    await fetch('/api/auth/logout');
    setUser(null);
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 text-gray-500 font-sans">
        Carregando ERP Distinto...
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4 font-sans">
      <div className="max-w-md w-full bg-white rounded-xl shadow-md p-8 border border-gray-100">
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-gray-900">ERP Distinto</h1>
          <p className="text-sm text-gray-500 mt-1">Gestão inteligente de propostas, contratos e financeiro</p>
        </div>

        {user ? (
          <div className="space-y-4 text-center">
            <div className="p-4 bg-emerald-50 text-emerald-800 rounded-lg text-sm">
              <p className="font-semibold">Bem-vindo(a), {user.nome}!</p>
              <p className="text-xs text-emerald-600 mt-1">{user.email}</p>
            </div>
            <button
              onClick={handleLogout}
              className="w-full py-2.5 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium text-sm shadow-sm"
            >
              Sair da Conta
            </button>
          </div>
        ) : (
          <form onSubmit={handleLogin} className="space-y-4">
            {erro && (
              <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {erro}
              </div>
            )}
            <div>
              <label className="block text-xs font-semibold text-gray-700 uppercase mb-1">E-mail</label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-black focus:border-transparent outline-none transition"
                placeholder="seu@email.com"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-700 uppercase mb-1">Senha</label>
              <input
                type="password"
                required
                value={senha}
                onChange={(e) => setSenha(e.target.value)}
                className="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-black focus:border-transparent outline-none transition"
                placeholder="••••••••"
              />
            </div>
            <button
              type="submit"
              className="w-full py-2.5 px-4 bg-black text-white rounded-lg hover:bg-gray-800 transition font-medium text-sm shadow-sm"
            >
              Entrar no Sistema
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
