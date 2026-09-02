import { useEffect, useState } from 'react';
import { Sidebar } from '@/components/Sidebar';
import { TopNav } from '@/components/TopNav';
import { DashboardView } from '@/components/DashboardView';
import { LancamentosView } from '@/components/LancamentosView';
import { ContasBancariasView } from '@/components/ContasBancariasView';
import { CustosFixosView } from '@/components/CustosFixosView';
import { ClientesView } from '@/components/ClientesView';
import { AsaasView } from '@/components/AsaasView';
import { PropostasView } from '@/components/PropostasView';
import { OrcamentosView } from '@/components/OrcamentosView';
import { OrcamentosAlbumView } from '@/components/OrcamentosAlbumView';
import { OrcamentosB2BView } from '@/components/OrcamentosB2BView';
import { ContratosView } from '@/components/ContratosView';
import { ModelosContratoView } from '@/components/ModelosContratoView';

export default function Home() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [email, setEmail] = useState('');
  const [senha, setSenha] = useState('');
  const [erro, setErro] = useState('');
  const [mensagemSucesso, setMensagemSucesso] = useState('');

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      if (params.get('senha_redefinida') === '1') {
        setMensagemSucesso('Senha redefinida com sucesso. Entre com a nova senha.');
        window.history.replaceState({}, '', window.location.pathname);
      }
    }
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
      <div className="min-h-screen flex items-center justify-center bg-[#050505] text-zinc-400 font-sans">
        Carregando ERP Distinto...
      </div>
    );
  }

  if (!user) {
    return (
      <div className="min-h-screen bg-[#050505] text-white flex flex-col items-center justify-center p-4 font-sans">
        <div className="max-w-md w-full bg-[#0c0c0c] border border-white/10 rounded-2xl shadow-2xl p-8">
          <div className="text-center mb-8">
            <div className="w-12 h-12 rounded-2xl bg-[#c5a880] text-black flex items-center justify-center font-black text-xl mx-auto mb-3 shadow-lg">
              D
            </div>
            <h1 className="text-2xl font-bold text-white tracking-tight">ERP Distinto</h1>
            <p className="text-xs text-zinc-400 mt-1">Gestão de propostas, contratos e financeiro</p>
          </div>

          <form onSubmit={handleLogin} className="space-y-4">
            {mensagemSucesso && (
              <div className="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs rounded-xl font-semibold text-center">
                {mensagemSucesso}
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
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-zinc-400 uppercase mb-1">Senha</label>
              <input
                type="password"
                required
                value={senha}
                onChange={(e) => setSenha(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-black/60 border border-white/10 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#c5a880] focus:border-transparent outline-none transition"
                placeholder="••••••••"
              />
            </div>
            <button
              type="submit"
              className="w-full py-3 px-4 bg-[#c5a880] hover:bg-[#d4b78f] text-black rounded-xl transition font-extrabold text-sm shadow-md mt-2"
            >
              Entrar no ERP
            </button>
            <div className="text-center mt-3">
              <a href="/esqueci-senha" className="text-xs text-zinc-400 hover:text-[#c5a880] transition font-medium">
                Esqueci minha senha
              </a>
            </div>
          </form>
        </div>
      </div>
    );
  }

  const renderActiveView = () => {
    switch (activeTab) {
      case 'dashboard':
        return <DashboardView />;
      case 'lancamentos':
        return <LancamentosView />;
      case 'bancos':
        return <ContasBancariasView />;
      case 'custos_fixos':
        return <CustosFixosView />;
      case 'clientes':
        return <ClientesView />;
      case 'asaas':
        return <AsaasView />;
      case 'propostas':
      case 'propostas_web':
        return <PropostasView />;
      case 'orcamentos':
        return <OrcamentosAlbumView />;
      case 'orcamentos_b2b':
        return <OrcamentosB2BView />;
      case 'solicitacoes':
        return <OrcamentosView />;
      case 'contratos':
        return <ContratosView />;
      case 'modelos_contrato':
        return <ModelosContratoView />;
      default:
        return (
          <div className="bg-[#0c0c0c] p-8 border border-white/10 shadow-2xs space-y-4 text-white">
            <h2 className="text-base font-bold text-white capitalize">{activeTab.replace('_', ' ')}</h2>
            <p className="text-xs text-zinc-500">Módulo ativo no ERP Distinto Serverless</p>
            <LancamentosView />
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-[#050505] flex font-sans text-white">
      <Sidebar user={user} activeTab={activeTab} setActiveTab={setActiveTab} onLogout={handleLogout} />
      <div className="flex-1 flex flex-col min-w-0 bg-[#050505]">
        <TopNav user={user} title={activeTab.toUpperCase().replace('_', ' ')} />
        <main className="p-6 flex-1 overflow-y-auto bg-[#050505]">{renderActiveView()}</main>
      </div>
    </div>
  );
}
