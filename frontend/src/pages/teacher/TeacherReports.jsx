import { useFetch } from '../../hooks/useApi';
import { Card, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function TeacherReports() {
  const { data: placement, loading: pl, error: ple, refetch: plr } = useFetch('/teacher/reports/placement');
  const { data: noInternship, loading: ni, error: nie, refetch: nir } = useFetch('/teacher/reports/no-internship');
  const { data: appStats, loading: al, error: ale, refetch: alr } = useFetch('/teacher/reports/applications');

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Laporan</h1>
        <p className="text-sm text-slate-500">Ringkasan penempatan dan lamaran siswa.</p>
      </div>

      {/* Placement Overview */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Statistik Penempatan</h2>
        </Card.Header>
        <Card.Body>
          {pl ? <LoadingState /> : ple ? <ErrorState message={ple} onRetry={plr} /> : (
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="rounded-lg bg-green-50 p-4 text-center">
                <p className="text-2xl font-bold text-green-700">{placement?.placed || 0}</p>
                <p className="text-xs text-green-600">Ditempatkan</p>
              </div>
              <div className="rounded-lg bg-yellow-50 p-4 text-center">
                <p className="text-2xl font-bold text-yellow-700">{placement?.in_progress || 0}</p>
                <p className="text-xs text-yellow-600">Dalam Proses</p>
              </div>
              <div className="rounded-lg bg-red-50 p-4 text-center">
                <p className="text-2xl font-bold text-red-700">{placement?.not_placed || 0}</p>
                <p className="text-xs text-red-600">Belum Ditempatkan</p>
              </div>
            </div>
          )}
        </Card.Body>
      </Card>

      {/* No Internship */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Siswa Belum PKL</h2>
        </Card.Header>
        <Card.Body>
          {ni ? <LoadingState /> : nie ? <ErrorState message={nie} onRetry={nir} /> : (
            !noInternship?.length ? (
              <EmptyState icon="🎉" title="Semua siswa sudah PKL!" />
            ) : (
              <div className="space-y-2">
                {noInternship.map((s) => (
                  <div key={s.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                    <div>
                      <p className="text-sm font-medium text-slate-900">{s.user?.name || s.name}</p>
                      <p className="text-xs text-slate-500">{s.nis} · {s.class}</p>
                    </div>
                    <span className="text-xs text-red-500">Belum ditempatkan</span>
                  </div>
                ))}
              </div>
            )
          )}
        </Card.Body>
      </Card>

      {/* Application Stats */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Statistik Lamaran</h2>
        </Card.Header>
        <Card.Body>
          {al ? <LoadingState /> : ale ? <ErrorState message={ale} onRetry={alr} /> : (
            <div className="grid gap-4 sm:grid-cols-4">
              <div className="rounded-lg bg-slate-50 p-4 text-center">
                <p className="text-2xl font-bold text-slate-700">{appStats?.total || 0}</p>
                <p className="text-xs text-slate-500">Total</p>
              </div>
              <div className="rounded-lg bg-yellow-50 p-4 text-center">
                <p className="text-2xl font-bold text-yellow-700">{appStats?.pending || 0}</p>
                <p className="text-xs text-yellow-600">Pending</p>
              </div>
              <div className="rounded-lg bg-green-50 p-4 text-center">
                <p className="text-2xl font-bold text-green-700">{appStats?.accepted || 0}</p>
                <p className="text-xs text-green-600">Diterima</p>
              </div>
              <div className="rounded-lg bg-red-50 p-4 text-center">
                <p className="text-2xl font-bold text-red-700">{appStats?.rejected || 0}</p>
                <p className="text-xs text-red-600">Ditolak</p>
              </div>
            </div>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}
