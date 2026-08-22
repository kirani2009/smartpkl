import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, ErrorState } from '../../components/ui';

export default function CompanyDashboard() {
  const { data: dashboard, loading, error, refetch } = useFetch('/company/dashboard');

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  const stats = dashboard || {};

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Dashboard Perusahaan</h1>
        <p className="text-sm text-slate-500">Ringkasan aktivitas perusahaan Anda.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Lowongan Aktif</p>
            <p className="mt-1 text-2xl font-bold text-blue-600">{stats.active_internships || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Pelamar</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{stats.total_applicants || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Partnership Aktif</p>
            <p className="mt-1 text-2xl font-bold text-green-600">{stats.active_partnerships || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Menunggu Review</p>
            <p className="mt-1 text-2xl font-bold text-yellow-600">{stats.pending_review || 0}</p>
          </Card.Body>
        </Card>
      </div>

      {/* Recent applicants */}
      {stats.recent_applicants?.length > 0 && (
        <Card>
          <Card.Header>
            <h2 className="font-semibold text-slate-900">Pelamar Terbaru</h2>
          </Card.Header>
          <Card.Body>
            <div className="space-y-3">
              {stats.recent_applicants.map((app) => (
                <div key={app.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{app.student?.user?.name}</p>
                    <p className="text-xs text-slate-500">{app.internship?.title}</p>
                  </div>
                  <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                    app.status === 'PENDING' ? 'bg-yellow-100 text-yellow-700' :
                    app.status === 'ACCEPTED' ? 'bg-green-100 text-green-700' :
                    'bg-slate-100 text-slate-600'
                  }`}>
                    {app.status}
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
