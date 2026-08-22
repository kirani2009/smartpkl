import { useFetch } from '../../hooks/useApi';
import { Card, StatusBadge, LoadingState, ErrorState } from '../../components/ui';

export default function StudentDashboard() {
  const { data: profile, loading: profileLoading, error: profileError } = useFetch('/me/student');
  const { data: applications, loading: appLoading } = useFetch('/student/applications', { params: { per_page: 5 } });
  const { data: matchings, loading: matchLoading } = useFetch('/student/matchings', { params: { per_page: 3 } });

  if (profileLoading) return <LoadingState />;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Dashboard Siswa</h1>
        <p className="text-sm text-slate-500">Selamat datang, {profile?.name || 'Siswa'}!</p>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Lamaran</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{applications?.meta?.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Dalam Review</p>
            <p className="mt-1 text-2xl font-bold text-yellow-600">
              {applications?.items?.filter((a) => a.status === 'PENDING').length || 0}
            </p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Diterima</p>
            <p className="mt-1 text-2xl font-bold text-green-600">
              {applications?.items?.filter((a) => a.status === 'ACCEPTED').length || 0}
            </p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Rekomendasi</p>
            <p className="mt-1 text-2xl font-bold text-blue-600">{matchings?.length || 0}</p>
          </Card.Body>
        </Card>
      </div>

      {/* Recent Applications */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Lamaran Terbaru</h2>
        </Card.Header>
        <Card.Body>
          {appLoading ? (
            <LoadingState text="Memuat..." />
          ) : !applications?.items?.length ? (
            <p className="py-4 text-center text-sm text-slate-500">Belum ada lamaran.</p>
          ) : (
            <div className="space-y-3">
              {applications.items.map((app) => (
                <div key={app.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{app.internship?.title}</p>
                    <p className="text-xs text-slate-500">{app.internship?.company?.name}</p>
                  </div>
                  <StatusBadge status={app.status} />
                </div>
              ))}
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Smart Matching */}
      {matchings?.length > 0 && (
        <Card>
          <Card.Header>
            <h2 className="font-semibold text-slate-900">Rekomendasi untuk Anda</h2>
          </Card.Header>
          <Card.Body>
            <div className="space-y-3">
              {matchings.map((m) => (
                <div key={m.internship?.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{m.internship?.title}</p>
                    <p className="text-xs text-slate-500">{m.internship?.company?.name} · {m.internship?.location}</p>
                  </div>
                  <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                    {m.match_score}% cocok
                  </span>
                </div>
              ))}
            </div>
          </Card.Body>
        </Card>
      )}
    </div>
  );
}
