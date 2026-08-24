import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, Input, StatusBadge, Pagination, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

export default function CompanyApplicants() {
  const [page, setPage] = useState(1);
  const [selectedApp, setSelectedApp] = useState(null);
  const [showInterviewModal, setShowInterviewModal] = useState(false);
  const [interviewForm, setInterviewForm] = useState({
    scheduled_at: '',
    mode: 'onsite',
    location: '',
    notes: '',
  });
  const [interviewTarget, setInterviewTarget] = useState(null);

  const { data, loading, error, refetch } = useFetch('/company/applications', {
    params: { per_page: 10, page },
  });
  const { mutate } = useMutation();

  const handleStatusChange = async (appId, newStatus) => {
    try {
      await mutate('put', `/company/applications/${appId}/status`, { status: newStatus });
      refetch();
      setSelectedApp(null);
    } catch {
      // handled
    }
  };

  const handleScheduleInterview = async () => {
    if (!interviewTarget || !interviewForm.scheduled_at) return;
    try {
      await mutate('post', `/company/applications/${interviewTarget.id}/interview`, interviewForm);
      setShowInterviewModal(false);
      setInterviewTarget(null);
      setInterviewForm({ scheduled_at: '', mode: 'onsite', location: '', notes: '' });
      refetch();
    } catch {
      // handled
    }
  };

  const apps = data?.items || data || [];

  // Determine which action buttons to show based on status
  const getActionButtons = (app) => {
    switch (app.status) {
      case 'PENDING':
        return (
          <>
            <Button size="sm" variant="secondary" onClick={() => handleStatusChange(app.id, 'REVIEWED')}>
              📋 Review
            </Button>
            <Button size="sm" onClick={() => handleStatusChange(app.id, 'ACCEPTED')}>
              ✅ Terima
            </Button>
            <Button size="sm" variant="danger" onClick={() => handleStatusChange(app.id, 'REJECTED')}>
              ❌ Tolak
            </Button>
          </>
        );
      case 'REVIEWED':
        return (
          <>
            <Button
              size="sm"
              onClick={() => {
                setInterviewTarget(app);
                setShowInterviewModal(true);
              }}
            >
              🗓️ Jadwalkan Interview
            </Button>
            <Button size="sm" onClick={() => handleStatusChange(app.id, 'ACCEPTED')}>
              ✅ Terima
            </Button>
            <Button size="sm" variant="danger" onClick={() => handleStatusChange(app.id, 'REJECTED')}>
              ❌ Tolak
            </Button>
          </>
        );
      case 'INTERVIEW':
        return (
          <>
            <Button size="sm" onClick={() => handleStatusChange(app.id, 'ACCEPTED')}>
              ✅ Terima
            </Button>
            <Button size="sm" variant="danger" onClick={() => handleStatusChange(app.id, 'REJECTED')}>
              ❌ Tolak
            </Button>
          </>
        );
      default:
        return null;
    }
  };

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
                    {getActionButtons(app)}
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
            {selectedApp.student?.school && (
              <div>
                <p className="text-xs text-slate-500">Sekolah</p>
                <p className="text-sm">{selectedApp.student.school.name}</p>
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
            <div className="flex gap-2 pt-2">
              {getActionButtons(selectedApp)}
            </div>
          </div>
        )}
      </Modal>

      {/* Interview Scheduling Modal */}
      <Modal
        open={showInterviewModal}
        onClose={() => { setShowInterviewModal(false); setInterviewTarget(null); }}
        title="Jadwalkan Interview"
      >
        <div className="space-y-4">
          {interviewTarget && (
            <p className="text-sm text-slate-600">
              Pelamar: <span className="font-medium">{interviewTarget.student?.user?.name}</span>
            </p>
          )}
          <Input
            label="Tanggal & Waktu *"
            name="scheduled_at"
            type="datetime-local"
            value={interviewForm.scheduled_at}
            onChange={(e) => setInterviewForm({ ...interviewForm, scheduled_at: e.target.value })}
            required
          />
          <div>
            <label className="block text-sm font-medium text-slate-700">Mode *</label>
            <select
              className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              value={interviewForm.mode}
              onChange={(e) => setInterviewForm({ ...interviewForm, mode: e.target.value })}
            >
              <option value="onsite">Onsite</option>
              <option value="online">Online</option>
            </select>
          </div>
          <Input
            label="Lokasi / Link"
            name="location"
            value={interviewForm.location}
            onChange={(e) => setInterviewForm({ ...interviewForm, location: e.target.value })}
            placeholder="Alamat kantor atau link meeting"
          />
          <Input
            label="Catatan"
            name="notes"
            value={interviewForm.notes}
            onChange={(e) => setInterviewForm({ ...interviewForm, notes: e.target.value })}
            placeholder="Instruksi untuk pelamar"
          />
          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={() => { setShowInterviewModal(false); setInterviewTarget(null); }}>
              Batal
            </Button>
            <Button onClick={handleScheduleInterview} disabled={!interviewForm.scheduled_at}>
              Jadwalkan
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
