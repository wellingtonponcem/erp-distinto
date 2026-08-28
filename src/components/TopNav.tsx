import React from 'react';

interface TopNavProps {
  user: { nome: string; email: string };
  title?: string;
}

export const TopNav: React.FC<TopNavProps> = ({ user, title = 'Dashboard' }) => {
  return (
    <header className="h-16 bg-[#050505] border-b border-white/10 px-6 flex items-center justify-between sticky top-0 z-40 font-sans text-white">
      <div>
        <h1 className="text-base font-extrabold text-white tracking-tight">{title}</h1>
      </div>

      <div className="flex items-center space-x-4">
        <div className="relative hidden md:block">
          <span className="material-symbols-outlined absolute left-3 top-2.5 text-zinc-500 text-sm leading-none">
            search
          </span>
          <input
            type="text"
            placeholder="Buscar lançamentos, clientes..."
            className="pl-9 pr-4 py-1.5 bg-black/60 border border-white/10 rounded-xl text-xs w-64 text-white placeholder-zinc-500 focus:bg-black focus:outline-none focus:border-[#c5a880] transition"
          />
        </div>

        <button className="p-2 text-zinc-400 hover:text-white hover:bg-white/10 rounded-xl transition relative">
          <span className="material-symbols-outlined text-lg leading-none">notifications</span>
          <span className="w-2 h-2 bg-[#c5a880] rounded-full absolute top-2 right-2"></span>
        </button>

        <div className="h-6 w-px bg-white/10"></div>

        <div className="flex items-center space-x-2">
          <div className="text-right hidden sm:block">
            <p className="text-xs font-bold text-white leading-tight">{user.nome}</p>
            <p className="text-[10px] text-zinc-500">{user.email}</p>
          </div>
        </div>
      </div>
    </header>
  );
};
