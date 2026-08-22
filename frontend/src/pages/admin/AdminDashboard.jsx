import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, ErrorState } from '../../components/ui';

export default function AdminDashboard() {
  const { data, loading, error, refetch } = useFetch('/admin/reports/overview');

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  const stats = data || {};

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Dashboard Admin</h1>
        <p className="text-sm text-slate-500">Ringkasan sistem SmartPKL.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Sekolah</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{stats.total_schools || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Perusahaan</p>
            <p className="mt-1 text-2xl font-bold text-blue-600">{stats.total_companies || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Siswa</p>
            <p className="mt-1 text-2xl font-bold text-green-600">{stats.total_students || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Guru</p>
            <p className="mt-1 text-2xl font-bold text-purple-600">{stats.total_teachers || 0}</p>
          </Card.Body>
        </Card>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Partnership Aktif</p>
            <p className="mt-1 text-2xl font-bold text-green-600">{stats.active_partnerships || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Lowongan Aktif</p>
            <p className="mt-1 text-2xl font-bold text-blue-600">{stats.active_internships || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Lamaran</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{stats.total_applications || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Siswa Ditempatkan</p>
            <p className="mt-1 text-2xl font-bold text-green-600">{stats.students_placed || 0}</p>
          </Card.Body>
        </Card>
      </div>
    </div>
  );
}
