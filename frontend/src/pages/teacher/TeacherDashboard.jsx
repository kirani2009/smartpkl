import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, ErrorState } from '../../components/ui';

export default function TeacherDashboard() {
  const { data: dashboard, loading, error, refetch } = useFetch('/teacher/dashboard');

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  // Backend response structure:
  // { school, students: {total, placed, without_internship}, partnerships: {total, active, pending}, recent_applications }
  const stats = dashboard || {};
  const school = stats.school || {};
  const students = stats.students || {};
  const partnerships = stats.partnerships || {};
  const recentApplications = stats.recent_applications || [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Dashboard Guru</h1>
        <p className="text-sm text-slate-500">{school.name ? `Sekolah: ${school.name}` : 'Selamat datang, Guru!'}</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Siswa</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{students.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Siswa Ditempatkan</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{students.placed || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Belum PKL</p>
            <p className="mt-1 text-2xl font-bold text-yellow-600">{students.without_internship || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Partnership Aktif</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{partnerships.active || 0}</p>
          </Card.Body>
        </Card>
      </div>

      {recentApplications.length > 0 && (
        <Card>
          <Card.Header>
            <h2 className="font-semibold text-slate-900">Lamaran Terbaru</h2>
          </Card.Header>
          <Card.Body>
            <div className="space-y-3">
              {recentApplications.map((app) => (
                <div key={app.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{app.student_name}</p>
                    <p className="text-xs text-slate-500">{app.internship_title}</p>
                  </div>
                  <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                    app.status === 'PENDING' ? 'bg-yellow-100 text-yellow-700' :
                    app.status === 'ACCEPTED' ? 'bg-brand-100 text-brand-700' :
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
