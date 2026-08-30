import { useFetch } from '../../hooks/useApi';
import { Card, StatusBadge, LoadingState, EmptyState } from '../../components/ui';

const statIcons = {
  total: '📋',
  pending: '⏳',
  accepted: '✅',
  recommendations: '🎯',
};

export default function StudentDashboard() {
  const { data: profile, loading: profileLoading, refetch: refetchProfile } = useFetch('/me/student');
  const { data: applications, loading: appLoading } = useFetch('/student/applications', { params: { per_page: 5 } });
  const { data: matchingsRes, loading: matchLoading } = useFetch('/student/matchings', { params: { limit: 3 } });

  if (profileLoading) return <LoadingState />;

  const totalApps = applications?.meta?.total || applications?.items?.length || 0;
  const pendingApps = applications?.items?.filter((a) => a.status === 'PENDING')?.length || 0;
  const acceptedApps = applications?.items?.filter((a) => a.status === 'ACCEPTED')?.length || 0;
  const matchings = matchingsRes?.items || [];
  const recCount = matchings.length;

  const stats = [
    { label: 'Total Lamaran', value: totalApps, icon: statIcons.total, gradient: 'from-blue-500 to-indigo-600', bgLight: 'bg-blue-50' },
    { label: 'Dalam Review', value: pendingApps, icon: statIcons.pending, gradient: 'from-amber-500 to-orange-600', bgLight: 'bg-amber-50' },
    { label: 'Diterima', value: acceptedApps, icon: statIcons.accepted, gradient: 'from-emerald-500 to-teal-600', bgLight: 'bg-emerald-50' },
    { label: 'Rekomendasi', value: recCount, icon: statIcons.recommendations, gradient: 'from-purple-500 to-violet-600', bgLight: 'bg-purple-50' },
  ];

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 p-8 text-white shadow-xl">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
        <div className="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-white/5 blur-2xl" />
        <div className="relative z-10">
          <p className="text-sm font-medium text-white/70">Selamat datang kembali</p>
          <h1 className="mt-1 text-2xl font-bold">{profile?.user?.name || 'Siswa'} 👋</h1>
          <p className="mt-2 max-w-lg text-sm text-white/80">
            Temukan lowongan PKL yang sesuai dengan minat dan jurusanmu. Mulai perjalanan karirmu sekarang!
          </p>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {stats.map((stat) => (
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

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent Applications */}
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
          <div className="border-b border-slate-100 px-6 py-4">
            <h2 className="text-sm font-semibold text-slate-900">Lamaran Terbaru</h2>
          </div>
          <div className="p-6">
            {appLoading ? (
              <LoadingState text="Memuat..." />
            ) : !applications?.items?.length ? (
              <EmptyState
                title="Belum ada lamaran"
                description="Mulai cari lowongan dan kirim lamaran pertama Anda."
                icon="📋"
              />
            ) : (
              <div className="space-y-3">
                {applications.items.map((app) => (
                  <div key={app.id} className="group flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 transition-all duration-200 hover:border-blue-200 hover:bg-blue-50/30 hover:shadow-sm">
                    <div className="flex items-center gap-3 min-w-0">
                      <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-sm">
                        {(app.internship?.title || 'P').charAt(0)}
                      </div>
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-slate-900 group-hover:text-blue-700">{app.internship?.title}</p>
                        <p className="truncate text-xs text-slate-500">{app.internship?.company?.profile?.name || app.internship?.company?.name}</p>
                      </div>
                    </div>
                    <StatusBadge status={app.status} />
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Smart Matching */}
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
          <div className="border-b border-slate-100 px-6 py-4">
            <h2 className="text-sm font-semibold text-slate-900">Rekomendasi untuk Anda</h2>
          </div>
          <div className="p-6">
            {matchLoading ? (
              <LoadingState text="Memuat..." />
            ) : !matchings.length ? (
              <div className="space-y-4">
                <EmptyState
                  title="Belum ada rekomendasi"
                  description="Tidak ditemukan lowongan yang sesuai dengan profil Anda saat ini."
                  icon="🎯"
                />
                {profile && (profile.profile_completeness ?? 0) < 100 && (
                  <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p className="text-xs font-semibold text-amber-800">💡 Lengkapi profil untuk rekomendasi lebih baik:</p>
                    <div className="mt-2 flex flex-wrap gap-1.5">
                      {(profile.missing_fields || []).map((field) => (
                        <span key={field} className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">
                          {field}
                        </span>
                      ))}
                    </div>
                    <a href="/student/profile" className="mt-3 inline-block text-xs font-semibold text-amber-700 underline hover:text-amber-900">Lengkapi Profil →</a>
                  </div>
                )}
              </div>
            ) : (
              <div className="space-y-3">
                {matchings.map((m) => (
                  <div key={m.internship?.id} className="group flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 transition-all duration-200 hover:border-purple-200 hover:bg-purple-50/30 hover:shadow-sm">
                    <div className="flex items-center gap-3 min-w-0">
                      <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-violet-600 text-sm font-bold text-white shadow-sm">
                        {(m.internship?.title || 'R').charAt(0)}
                      </div>
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-slate-900 group-hover:text-purple-700">{m.internship?.title}</p>
                        <p className="truncate text-xs text-slate-500">{m.internship?.company?.profile?.name || m.internship?.company?.name} · {m.internship?.location}</p>
                      </div>
                    </div>
                    <span className="flex-shrink-0 rounded-full bg-gradient-to-r from-purple-100 to-violet-100 px-2.5 py-1 text-xs font-bold text-purple-700">
                      {m.match_score}%
                    </span>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
