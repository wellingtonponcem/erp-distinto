import React from 'react';
import { LogOut } from 'lucide-react';

interface SidebarProps {
  user: { nome: string; email: string; nivel: number };
  activeTab: string;
  setActiveTab: (tab: string) => void;
  onLogout: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ user, activeTab, setActiveTab, onLogout }) => {
  const isAdmin = user.nivel === 1;
  const menuGroups = [
    {
      section: 'Principal',
      items: [{ id: 'dashboard', label: 'Dashboard', icon: 'home' }],
    },
    {
      section: 'Financeiro',
      adminOnly: true,
      items: [
        { id: 'lancamentos', label: 'Lançamentos', icon: 'receipt_long' },
        { id: 'bancos', label: 'Bancos', icon: 'account_balance' },
        { id: 'custos_fixos', label: 'Custos Fixos', icon: 'calculate' },
        { id: 'asaas', label: 'Asaas Pagamentos', icon: 'payments' },
      ],
    },
    {
      section: 'Serviços',
      items: [
        { id: 'servicos', label: 'Tabela de Preços', icon: 'inventory_2' },
        { id: 'consultor_ia', label: 'Consultor IA', icon: 'auto_awesome' },
      ],
    },
    {
      section: 'Comercial',
      items: [
        { id: 'propostas', label: 'Propostas Web', icon: 'description' },
        { id: 'orcamentos', label: 'Orçamentos', icon: 'calculate' },
        { id: 'orcamentos_b2b', label: 'Orçamentos B2B', icon: 'request_quote' },
        { id: 'solicitacoes', label: 'Solicitações', icon: 'mail' },
        { id: 'contratos', label: 'Contratos', icon: 'history_edu' },
        { id: 'modelos_contrato', label: 'Modelos de Contrato', icon: 'article' },
        { id: 'clientes', label: 'Clientes', icon: 'group' },
        { id: 'fornecedores', label: 'Fornecedores', icon: 'local_shipping' },
        { id: 'oportunidades', label: 'Oportunidades', icon: 'trending_up' },
      ],
    },
  ].filter((g: any) => !(g.adminOnly && !isAdmin));

  return (
    <aside className="w-64 bg-[#050505] border-r border-white/10 min-h-screen flex flex-col justify-between font-sans select-none shrink-0 text-white">
      <div className="p-4 space-y-6">
        {/* Brand */}
        <div className="flex items-center space-x-3 px-2 py-2">
          <div className="w-9 h-9 rounded-xl bg-[#c5a880] text-black flex items-center justify-center font-black text-lg shadow-md shrink-0">
            D
          </div>
          <div>
            <h2 className="font-extrabold text-white leading-none tracking-tight text-base">DISTINTO</h2>
            <span className="text-[10px] font-bold text-[#c5a880] uppercase tracking-widest">AGENCY ERP</span>
          </div>
        </div>

        {/* Navigation */}
        <nav className="space-y-5">
          {menuGroups.map((group, gIdx) => (
            <div key={gIdx}>
              <div className="px-3 text-[10px] font-bold uppercase tracking-wider text-zinc-500 mb-2">
                {group.section}
              </div>
              <div className="space-y-1">
                {group.items.map((item) => {
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all ${
                        isActive
                          ? 'bg-[#c5a880] text-black shadow-md font-bold'
                          : 'text-zinc-400 hover:bg-white/5 hover:text-white'
                      }`}
                    >
                      <span className="material-symbols-outlined text-lg leading-none shrink-0">{item.icon}</span>
                      <span className="truncate">{item.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
      </div>

      {/* User Info & Logout */}
      <div className="p-4 border-t border-white/10 bg-black/40">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3 min-w-0 pr-2">
            <div className="w-8 h-8 rounded-full bg-white/10 border border-white/10 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
              {user.nome ? user.nome.charAt(0) : 'U'}
            </div>
            <div className="truncate">
              <p className="text-xs font-bold text-white truncate leading-tight">{user.nome}</p>
              <p className="text-[10px] text-zinc-500 truncate leading-tight">{user.email}</p>
            </div>
          </div>

          <button
            onClick={onLogout}
            className="p-2 text-zinc-400 hover:text-red-400 hover:bg-red-500/10 rounded-xl transition shrink-0"
            title="Sair da conta"
          >
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>
  );
};
