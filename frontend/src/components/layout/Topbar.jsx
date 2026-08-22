import { useAuth } from '../../contexts/AuthContext';

const roleLabels = {
  student: 'Siswa',
  teacher: 'Guru',
  company: 'Perusahaan',
  admin: 'Admin',
};

export default function Topbar({ onMenuToggle }) {
  const { user, logout } = useAuth();

  return (
    <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-6 backdrop-blur">
      <button
        onClick={onMenuToggle}
        className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
      >
        ☰
      </button>

      <div className="ml-auto flex items-center gap-4">
        <div className="text-right">
          <p className="text-sm font-medium text-slate-900">{user?.name}</p>
          <p className="text-xs text-slate-500">{roleLabels[user?.role] || user?.role}</p>
        </div>
        <button
          onClick={logout}
          className="rounded-lg px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
        >
          Keluar
        </button>
      </div>
    </header>
  );
}
