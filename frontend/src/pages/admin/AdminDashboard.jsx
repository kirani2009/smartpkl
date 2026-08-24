import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, ErrorState } from '../../components/ui';

export default function AdminDashboard() {
  const { data, loading, error, refetch } = useFetch('/admin/reports/overview');

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  // Backend response structure:
  // { users, schools, companies, students, partnerships, internships, applications }
  const stats = data || {};
  const users = stats.users || {};
  const schools = stats.schools || {};
  const companies = stats.companies || {};
  const students = stats.students || {};
  const partnerships = stats.partnerships || {};
  const internships = stats.internships || {};
  const applications = stats.applications || {};

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
            <p className="mt-1 text-2xl font-bold text-slate-900">{schools.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Perusahaan</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{companies.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Siswa</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{students.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Guru</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{users.teachers || 0}</p>
          </Card.Body>
        </Card>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Partnership Aktif</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{partnerships.active || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Lowongan Aktif</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{internships.published || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Lamaran</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{applications.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Lamaran Diterima</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{applications.accepted || 0}</p>
          </Card.Body>
        </Card>
      </div>
    </div>
  );
}
