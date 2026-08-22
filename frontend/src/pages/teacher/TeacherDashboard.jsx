import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, ErrorState } from '../../components/ui';

export default function TeacherDashboard() {
  const { data: dashboard, loading, error, refetch } = useFetch('/teacher/dashboard');
  const { data: profile } = useFetch('/me/teacher');

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  const stats = dashboard || {};

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Dashboard Guru</h1>
        <p className="text-sm text-slate-500">Selamat datang, Guru {profile?.name || ''}!</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Siswa</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{stats.total_students || 0}</p>
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
            <p className="text-sm text-slate-500">Siswa Ditempatkan</p>
            <p className="mt-1 text-2xl font-bold text-blue-600">{stats.students_placed || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Menunggu Keputusan</p>
            <p className="mt-1 text-2xl font-bold text-yellow-600">{stats.pending_decisions || 0}</p>
          </Card.Body>
        </Card>
      </div>

      {/* Recent partnerships */}
      {stats.recent_partnerships?.length > 0 && (
        <Card>
          <Card.Header>
            <h2 className="font-semibold text-slate-900">Partnership Terbaru</h2>
          </Card.Header>
          <Card.Body>
            <div className="space-y-3">
              {stats.recent_partnerships.map((p) => (
                <div key={p.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{p.company?.name}</p>
                    <p className="text-xs text-slate-500">Diajukan: {new Date(p.created_at).toLocaleDateString('id-ID')}</p>
                  </div>
                  <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                    p.status === 'ACTIVE' ? 'bg-green-100 text-green-700' :
                    p.status === 'PENDING' ? 'bg-yellow-100 text-yellow-700' :
                    'bg-slate-100 text-slate-600'
                  }`}>
                    {p.status}
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
