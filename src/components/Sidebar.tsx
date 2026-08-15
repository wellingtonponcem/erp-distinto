import React from 'react';

interface SidebarProps {
  user: { nome: string; email: string; nivel: number };
  activeTab: string;
  setActiveTab: (tab: string) => void;
  onLogout: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ user, activeTab, setActiveTab, onLogout }) => {
  const menuItems = [
    { section: 'Principal', items: [{ id: 'dashboard', label: 'Dashboard', icon: 'home' }] },
    {
      section: 'Financeiro',
      items: [
        { id: 'lancamentos', label: 'Lançamentos', icon: 'receipt_long' },
        { id: 'bancos', label: 'Bancos & Contas', icon: 'account_balance' },
        { id: 'custos_fixos', label: 'Custos Fixos', icon: 'calculate' },
        { id: 'asaas', label: 'Asaas Pagamentos', icon: 'payments' },
      ],
    },
    {
      section: 'Comercial & Vendas',
      items: [
        { id: 'propostas', label: 'Propostas Web', icon: 'description' },
        { id: 'orcamentos', label: 'Orçamentos', icon: 'calculate' },
        { id: 'contratos', label: 'Contratos', icon: 'history_edu' },
        { id: 'clientes', label: 'Clientes', icon: 'group' },
        { id: 'fornecedores', label: 'Fornecedores', icon: 'local_shipping' },
        { id: 'oportunidades', label: 'Oportunidades', icon: 'trending_up' },
      ],
    },
    {
      section: 'Serviços & IA',
      items: [
        { id: 'servicos', label: 'Tabela de Preços', icon: 'inventory_2' },
        { id: 'consultor_ia', label: 'Consultor IA', icon: 'auto_awesome' },
      ],
    },
  ];

  return (
    <aside className="w-64 bg-white border-r border-gray-200 min-h-screen flex flex-col justify-between p-4 font-sans select-none shrink-0">
      <div>
        {/* Brand */}
        <div className="flex items-center space-x-3 px-2 py-3 mb-6">
          <div className="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-bold text-lg shadow-sm">
            D
          </div>
          <div>
            <h2 className="font-bold text-gray-900 leading-none tracking-tight text-base">DISTINTO</h2>
            <span className="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">ERP AGENCY</span>
          </div>
        </div>

        {/* Navigation Items */}
        <nav className="space-y-6">
          {menuItems.map((group, idx) => (
            <div key={idx}>
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
                      className={`w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition ${
                        isActive
                          ? 'bg-black text-white shadow-sm'
                          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                      }`}
                    >
                      <span className="material-symbols-outlined text-lg">{item.icon}</span>
                      <span>{item.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
      </div>

      {/* User Footer */}
      <div className="pt-4 border-t border-gray-100">
        <div className="flex items-center justify-between px-2 py-2">
          <div className="flex items-center space-x-3 overflow-hidden">
            <div className="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
              {user.nome ? user.nome.charAt(0) : 'U'}
            </div>
            <div className="truncate">
              <p className="text-xs font-bold text-gray-900 truncate">{user.nome}</p>
              <p className="text-[10px] text-gray-400 truncate">{user.email}</p>
            </div>
          </div>
          <button
            onClick={onLogout}
            title="Sair"
            className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
          >
            <span className="material-symbols-outlined text-lg">logout</span>
          </button>
        </div>
      </div>
    </aside>
  );
};
