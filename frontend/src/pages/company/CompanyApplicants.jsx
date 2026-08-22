import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, StatusBadge, Pagination, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

export default function CompanyApplicants() {
  const [page, setPage] = useState(1);
  const [selectedApp, setSelectedApp] = useState(null);

  const { data, loading, error, refetch } = useFetch('/company/applications', {
    params: { per_page: 10, page },
  });
  const { mutate } = useMutation();

  const handleAction = async (appId, action) => {
    try {
      await mutate('put', `/company/applications/${appId}/status`, { status: action });
      refetch();
      setSelectedApp(null);
    } catch {
      // handled
    }
  };

  const apps = data?.items || data || [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Pelamar</h1>
        <p className="text-sm text-slate-500">Tinjau dan kelola pelamar untuk lowongan Anda.</p>
      </div>

      {loading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !apps.length ? (
        <EmptyState title="Belum ada pelamar" description="Pelamar akan muncul di sini setelah siswa mengirim lamaran." />
      ) : (
        <>
          <div className="space-y-3">
            {apps.map((app) => (
              <Card key={app.id}>
                <Card.Body>
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{app.student?.user?.name || '-'}</p>
                      <p className="text-xs text-slate-500">{app.internship?.title}</p>
                      <p className="text-xs text-slate-400">
                        Dilamar: {app.applied_at ? new Date(app.applied_at).toLocaleDateString('id-ID') : '-'}
                      </p>
                    </div>
                    <StatusBadge status={app.status} />
                  </div>

                  {app.message && (
                    <p className="mt-2 rounded bg-slate-50 p-2 text-xs text-slate-600 italic">"{app.message}"</p>
                  )}

                  <div className="mt-3 flex gap-2">
                    <Button size="sm" variant="secondary" onClick={() => setSelectedApp(app)}>
                      👁️ Detail
                    </Button>
                    {app.status === 'PENDING' && (
                      <>
                        <Button size="sm" onClick={() => handleAction(app.id, 'ACCEPTED')}>
                          ✅ Terima
                        </Button>
                        <Button size="sm" variant="danger" onClick={() => handleAction(app.id, 'REJECTED')}>
                          ❌ Tolak
                        </Button>
                      </>
                    )}
                  </div>
                </Card.Body>
              </Card>
            ))}
          </div>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </>
      )}

      {/* Detail Modal */}
      <Modal open={!!selectedApp} onClose={() => setSelectedApp(null)} title="Detail Pelamar">
        {selectedApp && (
          <div className="space-y-3">
            <div>
              <p className="text-xs text-slate-500">Nama</p>
              <p className="text-sm font-medium">{selectedApp.student?.user?.name}</p>
            </div>
            <div>
              <p className="text-xs text-slate-500">Lowongan</p>
              <p className="text-sm">{selectedApp.internship?.title}</p>
            </div>
            <div>
              <p className="text-xs text-slate-500">Status</p>
              <StatusBadge status={selectedApp.status} />
            </div>
            {selectedApp.student?.major && (
              <div>
                <p className="text-xs text-slate-500">Jurusan</p>
                <p className="text-sm">{selectedApp.student.major.name}</p>
              </div>
            )}
            {selectedApp.student?.skills?.length > 0 && (
              <div>
                <p className="text-xs text-slate-500">Skill</p>
                <div className="mt-1 flex flex-wrap gap-1">
                  {selectedApp.student.skills.map((s) => (
                    <span key={s.id} className="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">{s.name}</span>
                  ))}
                </div>
              </div>
            )}
            {selectedApp.status === 'PENDING' && (
              <div className="flex gap-2 pt-2">
                <Button onClick={() => handleAction(selectedApp.id, 'ACCEPTED')}>✅ Terima</Button>
                <Button variant="danger" onClick={() => handleAction(selectedApp.id, 'REJECTED')}>❌ Tolak</Button>
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}
