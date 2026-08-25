import React from 'react';

interface SidebarProps {
  user: { nome: string; email: string; nivel: number };
  activeTab: string;
  setActiveTab: (tab: string) => void;
  onLogout: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ user, activeTab, setActiveTab, onLogout }) => {
  const menuGroups = [
    {
      section: 'Principal',
      items: [{ id: 'dashboard', label: 'Dashboard', icon: 'home' }],
    },
    {
      section: 'Financeiro',
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
        { id: 'solicitacoes', label: 'Solicitações', icon: 'mail' },
        { id: 'contratos', label: 'Contratos', icon: 'history_edu' },
        { id: 'modelos_contrato', label: 'Modelos de Contrato', icon: 'article' },
        { id: 'clientes', label: 'Clientes', icon: 'group' },
        { id: 'fornecedores', label: 'Fornecedores', icon: 'local_shipping' },
        { id: 'oportunidades', label: 'Oportunidades', icon: 'trending_up' },
      ],
    },
  ];

  return (
    <aside className="w-64 bg-white border-r border-gray-200 min-h-screen flex flex-col justify-between font-sans select-none shrink-0 shadow-xs">
      <div className="p-4 space-y-6">
        {/* Brand */}
        <div className="flex items-center space-x-3 px-2 py-2">
          <div className="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-bold text-lg shadow-sm">
            D
          </div>
          <div>
            <h2 className="font-extrabold text-gray-900 leading-none tracking-tight text-base">DISTINTO</h2>
            <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">AGENCY ERP</span>
          </div>
        </div>

        {/* Navigation */}
        <nav className="space-y-5">
          {menuGroups.map((group, gIdx) => (
            <div key={gIdx}>
              <div className="px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">
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
                          ? 'bg-black text-white shadow-sm font-bold'
                          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
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
      <div className="p-4 border-t border-gray-100 bg-gray-50/50">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3 min-w-0 pr-2">
            <div className="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
              {user.nome ? user.nome.charAt(0) : 'U'}
            </div>
            <div className="truncate">
              <p className="text-xs font-bold text-gray-900 truncate leading-tight">{user.nome}</p>
              <p className="text-[10px] text-gray-400 truncate">{user.email}</p>
            </div>
          </div>
          <button
            onClick={onLogout}
            title="Sair da Conta"
            className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition shrink-0"
          >
            <span className="material-symbols-outlined text-lg leading-none">logout</span>
          </button>
        </div>
      </div>
    </aside>
  );
};
