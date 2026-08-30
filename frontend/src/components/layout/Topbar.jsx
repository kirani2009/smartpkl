import { useAuth } from '../../contexts/AuthContext';

const roleLabels = {
  student: 'Siswa',
  teacher: 'Guru',
  company: 'Perusahaan',
  admin: 'Admin',
};

const roleColors = {
  student: 'bg-blue-100 text-blue-700',
  teacher: 'bg-brand-100 text-brand-700',
  company: 'bg-violet-100 text-violet-700',
  admin: 'bg-amber-100 text-amber-700',
};

export default function Topbar({ onMenuToggle }) {
  const { user, logout } = useAuth();

  const initials = user?.name
    ? user.name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
    : '?';

  return (
    <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/80 px-6 backdrop-blur-sm">
      {/* Mobile menu */}
      <button
        onClick={onMenuToggle}
        className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 lg:hidden"
      >
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      {/* Right side */}
      <div className="ml-auto flex items-center gap-4">
        {/* Role badge */}
        <span className={`hidden rounded-full px-2.5 py-1 text-xs font-medium sm:inline-flex ${roleColors[user?.role] || 'bg-slate-100 text-slate-600'}`}>
          {roleLabels[user?.role] || user?.role}
        </span>

        {/* User info */}
        <div className="flex items-center gap-3">
          <div className="text-right">
            <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
            <p className="text-xs text-slate-500">{user?.email}</p>
          </div>
          {/* Avatar */}
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-600 ring-2 ring-white">
            {initials}
          </div>
        </div>

        {/* Logout */}
        <button
          onClick={logout}
          className="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-500 transition-colors hover:bg-red-50 hover:text-red-600"
        >
          Keluar
        </button>
      </div>
    </header>
  );
}
