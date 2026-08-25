import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, ErrorState } from '../../components/ui';

export default function CompanyDashboard() {
  const navigate = useNavigate();
  const { data: dashboard, loading, error, refetch } = useFetch('/company/dashboard');
  const { data: profile, loading: profileLoading } = useFetch('/me/company');

  // Check if company profile exists
  useEffect(() => {
    if (!loading && !profileLoading) {
      // API returns { success, message, data: CompanyResource }
      // CompanyResource has a 'profile' nested object
      const hasProfile = profile?.profile;
      if (error && error.includes('belum dibuat')) {
        navigate('/company/profile/setup', { replace: true });
      } else if (!hasProfile && !error) {
        navigate('/company/profile/setup', { replace: true });
      }
    }
  }, [error, profile, loading, profileLoading, navigate]);

  if (loading || profileLoading) return <LoadingState />;
  if (error && !error.includes('belum dibuat')) return <ErrorState message={error} onRetry={refetch} />;

  if (error?.includes('belum dibuat') || (profile && !profile?.profile)) {
    return <LoadingState text="Mengalihkan ke pengaturan profil..." />;
  }

  // Backend response structure:
  // { company, partnerships, internship_listings, applicants, recent_applications }
  const stats = dashboard || {};

  const internshipListings = stats.internship_listings || {};
  const applicants = stats.applicants || {};
  const partnerships = stats.partnerships || {};
  const recentApplications = stats.recent_applications || [];

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
            <p className="mt-1 text-2xl font-bold text-brand-600">{internshipListings.published || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Total Pelamar</p>
            <p className="mt-1 text-2xl font-bold text-slate-900">{applicants.total || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Partnership Aktif</p>
            <p className="mt-1 text-2xl font-bold text-brand-600">{partnerships.active || 0}</p>
          </Card.Body>
        </Card>
        <Card>
          <Card.Body>
            <p className="text-sm text-slate-500">Menunggu Review</p>
            <p className="mt-1 text-2xl font-bold text-yellow-600">{applicants.pending || 0}</p>
          </Card.Body>
        </Card>
      </div>

      {recentApplications.length > 0 && (
        <Card>
          <Card.Header>
            <h2 className="font-semibold text-slate-900">Pelamar Terbaru</h2>
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
