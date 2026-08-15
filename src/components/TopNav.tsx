import React from 'react';

interface TopNavProps {
  user: { nome: string; email: string };
  title?: string;
}

export const TopNav: React.FC<TopNavProps> = ({ user, title = 'Dashboard' }) => {
  return (
    <header className="h-16 bg-white border-b border-gray-200 px-6 flex items-center justify-between sticky top-0 z-40 font-sans">
      <div className="flex items-center space-x-4">
        <h1 className="text-lg font-bold text-gray-900">{title}</h1>
      </div>

      <div className="flex items-center space-x-4">
        <div className="relative hidden md:block">
          <span className="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-sm">search</span>
          <input
            type="text"
            placeholder="Buscar lançamentos, clientes..."
            className="pl-9 pr-4 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs w-64 focus:bg-white focus:outline-none focus:ring-2 focus:ring-black transition"
          />
        </div>

        <button className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition relative">
          <span className="material-symbols-outlined text-xl">notifications</span>
          <span className="w-2 h-2 bg-emerald-500 rounded-full absolute top-2 right-2"></span>
        </button>

        <div className="h-6 w-px bg-gray-200"></div>

        <div className="flex items-center space-x-2">
          <div className="text-right hidden sm:block">
            <p className="text-xs font-bold text-gray-900">{user.nome}</p>
            <p className="text-[10px] text-gray-400">{user.email}</p>
          </div>
        </div>
      </div>
    </header>
  );
};
