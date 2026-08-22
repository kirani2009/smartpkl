import { NavLink } from 'react-router-dom';

const navConfig = {
  student: [
    { to: '/student/dashboard', label: 'Dashboard', icon: '🏠' },
    { to: '/internships', label: 'Cari Lowongan', icon: '🔍' },
    { to: '/student/applications', label: 'Lamaran Saya', icon: '📄' },
    { to: '/student/interviews', label: 'Interview', icon: '🗓️' },
    { to: '/student/profile', label: 'Profil Saya', icon: '👤' },
  ],
  teacher: [
    { to: '/teacher/dashboard', label: 'Dashboard', icon: '🏠' },
    { to: '/teacher/students', label: 'Siswa', icon: '👨‍🎓' },
    { to: '/teacher/partnerships', label: 'Partnership', icon: '🤝' },
    { to: '/teacher/reports', label: 'Laporan', icon: '📊' },
  ],
  company: [
    { to: '/company/dashboard', label: 'Dashboard', icon: '🏠' },
    { to: '/company/profile/setup', label: 'Profil Perusahaan', icon: '🏢' },
    { to: '/company/partnerships', label: 'Partnership', icon: '🤝' },
    { to: '/company/internships', label: 'Lowongan PKL', icon: '📋' },
    { to: '/company/applicants', label: 'Pelamar', icon: '👥' },
  ],
  admin: [
    { to: '/admin/dashboard', label: 'Dashboard', icon: '🏠' },
  ],
};

export default function Sidebar({ role, onNavigate }) {
  const items = navConfig[role] || navConfig.student;

  return (
    <aside className="flex h-screen w-64 flex-col border-r border-slate-200 bg-white">
      <div className="flex items-center gap-2 border-b border-slate-200 px-6 py-5">
        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">
          P
        </span>
        <span className="text-lg font-bold text-slate-900">SmartPKL</span>
      </div>

      <nav className="flex-1 space-y-1 px-3 py-4">
        {items.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            onClick={onNavigate}
            className={({ isActive }) =>
              `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                isActive
                  ? 'bg-brand-50 text-brand-700'
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              }`
            }
          >
            <span className="text-base">{item.icon}</span>
            {item.label}
          </NavLink>
        ))}
      </nav>
    </aside>
  );
}
