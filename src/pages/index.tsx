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
import { ContratosView } from '@/components/ContratosView';
import { ModelosContratoView } from '@/components/ModelosContratoView';

export default function Home() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('dashboard');
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

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4 font-sans">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
          <div className="text-center mb-8">
            <div className="w-12 h-12 rounded-2xl bg-black text-white flex items-center justify-center font-bold text-xl mx-auto mb-3 shadow-md">
              D
            </div>
            <h1 className="text-2xl font-bold text-gray-900">ERP Distinto</h1>
            <p className="text-xs text-gray-400 mt-1">Gestão de propostas, contratos e financeiro</p>
          </div>

          <form onSubmit={handleLogin} className="space-y-4">
            {erro && (
              <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-semibold">
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
                className="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-black focus:border-transparent outline-none transition"
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
                className="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-black focus:border-transparent outline-none transition"
                placeholder="••••••••"
              />
            </div>
            <button
              type="submit"
              className="w-full py-3 px-4 bg-black text-white rounded-xl hover:bg-gray-800 transition font-bold text-sm shadow-md"
            >
              Entrar no ERP
            </button>
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
      case 'orcamentos':
        return <PropostasView />;
      case 'solicitacoes':
        return <OrcamentosView />;
      case 'contratos':
        return <ContratosView />;
      case 'modelos_contrato':
        return <ModelosContratoView />;
      default:
        return (
          <div className="bg-white p-8 rounded-2xl border border-gray-200/80 shadow-2xs space-y-4">
            <h2 className="text-base font-bold text-gray-900 capitalize">{activeTab.replace('_', ' ')}</h2>
            <p className="text-xs text-gray-400">Módulo ativo no ERP Distinto Serverless</p>
            <LancamentosView />
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex font-sans text-gray-900">
      <Sidebar user={user} activeTab={activeTab} setActiveTab={setActiveTab} onLogout={handleLogout} />
      <div className="flex-1 flex flex-col min-w-0">
        <TopNav user={user} title={activeTab.toUpperCase().replace('_', ' ')} />
        <main className="p-6 flex-1 overflow-y-auto">{renderActiveView()}</main>
      </div>
    </div>
  );
}
