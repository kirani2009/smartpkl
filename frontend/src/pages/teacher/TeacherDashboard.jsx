import { useFetch } from '../../hooks/useApi';
import { LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function TeacherDashboard() {
  const { data: dashboard, loading, error, refetch } = useFetch('/teacher/dashboard');

  if (loading) return <LoadingState text="Memuat dashboard..." />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  if (!dashboard) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Dashboard Guru</h1>
          <p className="text-sm text-slate-500">Selamat datang, Guru!</p>
        </div>
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl text-center">
          <p className="text-sm text-slate-500">Data dashboard belum tersedia.</p>
        </div>
      </div>
    );
  }

  const school = dashboard.school || {};
  const students = dashboard.students || {};
  const partnerships = dashboard.partnerships || {};
  const recentApplications = dashboard.recent_applications || [];

  const statCards = [
    { label: 'Total Siswa', value: students.total || 0, icon: '👨‍🎓', gradient: 'from-blue-500 to-indigo-600', bgLight: 'bg-blue-50' },
    { label: 'Siswa Ditempatkan', value: students.placed || 0, icon: '✅', gradient: 'from-emerald-500 to-teal-600', bgLight: 'bg-emerald-50' },
    { label: 'Belum PKL', value: students.without_internship || 0, icon: '⏳', gradient: 'from-amber-500 to-orange-600', bgLight: 'bg-amber-50' },
    { label: 'Partnership Aktif', value: partnerships.active || 0, icon: '🤝', gradient: 'from-violet-500 to-purple-600', bgLight: 'bg-violet-50' },
  ];

  const statusColors = {
    PENDING: 'bg-amber-50 text-amber-700 border border-amber-200',
    REVIEWED: 'bg-blue-50 text-blue-700 border border-blue-200',
    ACCEPTED: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    REJECTED: 'bg-red-50 text-red-700 border border-red-200',
  };

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-700 p-8 text-white shadow-xl">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
        <div className="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-white/5 blur-2xl" />
        <div className="relative z-10">
          <p className="text-sm font-medium text-white/70">Selamat datang kembali</p>
          <h1 className="mt-1 text-2xl font-bold">Dashboard Guru 📚</h1>
          <p className="mt-2 max-w-lg text-sm text-white/80">
            {school.name ? `Sekolah: ${school.name}` : 'Kelola siswa, jurusan, dan partnership dari sini.'}
          </p>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {statCards.map((stat) => (
          <div
            key={stat.label}
            className="group relative overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 p-5 shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/50"
          >
            <div className="flex items-start justify-between">
              <div>
                <p className="text-xs font-medium text-slate-500">{stat.label}</p>
                <p className="mt-2 text-3xl font-bold text-slate-900">{stat.value}</p>
              </div>
              <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${stat.bgLight} text-2xl shadow-sm transition-transform duration-300 group-hover:scale-110`}>
                {stat.icon}
              </div>
            </div>
            <div className={`absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r ${stat.gradient} opacity-0 transition-opacity duration-300 group-hover:opacity-100`} />
          </div>
        ))}
      </div>

      {/* Recent Applications */}
      <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
        <div className="border-b border-slate-100 px-6 py-4">
          <h2 className="text-sm font-semibold text-slate-900">Lamaran Terbaru</h2>
        </div>
        <div className="p-6">
          {!recentApplications.length ? (
            <EmptyState
              title="Belum ada lamaran terbaru"
              description="Lamaran akan muncul di sini setelah siswa mengirim lamaran."
              icon="📋"
            />
          ) : (
            <div className="space-y-3">
              {recentApplications.map((app) => (
                <div key={app.id} className="group flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 transition-all duration-200 hover:border-blue-200 hover:bg-blue-50/30 hover:shadow-sm">
                  <div className="flex items-center gap-3 min-w-0">
                    <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-violet-600 text-sm font-bold text-white shadow-sm">
                      {(app.student_name || 'S').charAt(0)}
                    </div>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium text-slate-900 group-hover:text-blue-700">{app.student_name}</p>
                      <p className="truncate text-xs text-slate-500">{app.internship_title}</p>
                    </div>
                  </div>
                  <span className={`flex-shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${statusColors[app.status] || 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                    {app.status}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
