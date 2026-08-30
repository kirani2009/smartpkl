import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Pagination, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';
import api from '../../api/api';

const statusColors = {
  PENDING: 'bg-amber-50 text-amber-700 border border-amber-200',
  REVIEWED: 'bg-blue-50 text-blue-700 border border-blue-200',
  INTERVIEW: 'bg-purple-50 text-purple-700 border border-purple-200',
  ACCEPTED: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
  REJECTED: 'bg-red-50 text-red-700 border border-red-200',
  CANCELLED: 'bg-slate-100 text-slate-600 border border-slate-200',
};

const statusLabels = {
  PENDING: '⏳ Pending',
  REVIEWED: '🔍 Review',
  INTERVIEW: '💬 Interview',
  ACCEPTED: '✅ Diterima',
  REJECTED: '❌ Ditolak',
  CANCELLED: '🚫 Dibatalkan',
};

export default function CompanyApplicants() {
  const [page, setPage] = useState(1);
  const [selectedApp, setSelectedApp] = useState(null);
  const [detailData, setDetailData] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);

  const [actionModal, setActionModal] = useState(null);
  const [actionNote, setActionNote] = useState('');
  const [actionSaving, setActionSaving] = useState(false);

  const [showInterviewModal, setShowInterviewModal] = useState(false);
  const [interviewForm, setInterviewForm] = useState({ scheduled_at: '', mode: 'onsite', location: '', notes: '' });
  const [interviewTarget, setInterviewTarget] = useState(null);

  const { data, loading, error, refetch } = useFetch('/company/applications', {
    params: { per_page: 10, page },
  });
  const { mutate } = useMutation();

  const handleViewDetail = async (app) => {
    setSelectedApp(app);
    setDetailLoading(true);
    try {
      const { data: res } = await api.get(`/company/applications/${app.id}`);
      if (res.success) setDetailData(res.data);
    } catch {
      setDetailData(app);
    } finally { setDetailLoading(false); }
  };

  const handleStatusChange = async (appId, newStatus, note = '') => {
    try {
      const payload = { status: newStatus };
      if (note) payload.note = note;
      await mutate('put', `/company/applications/${appId}/status`, payload);
      refetch();
      setSelectedApp(null);
      setDetailData(null);
      setActionModal(null);
      setActionNote('');
    } catch { /* handled */ }
  };

  const handleActionConfirm = async () => {
    if (!actionModal) return;
    setActionSaving(true);
    try { await handleStatusChange(actionModal.app.id, actionModal.action, actionNote); }
    finally { setActionSaving(false); }
  };

  const handleScheduleInterview = async () => {
    if (!interviewTarget || !interviewForm.scheduled_at) return;
    try {
      await mutate('post', `/company/applications/${interviewTarget.id}/interview`, interviewForm);
      setShowInterviewModal(false);
      setInterviewTarget(null);
      setInterviewForm({ scheduled_at: '', mode: 'onsite', location: '', notes: '' });
      refetch();
    } catch { /* handled */ }
  };

  const apps = data?.items || data || [];

  const getActionButtons = (app) => {
    switch (app.status) {
      case 'PENDING':
        return (
          <>
            <button onClick={() => handleStatusChange(app.id, 'REVIEWED')}
              className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-all hover:bg-slate-50">📋 Review</button>
            <button onClick={() => setActionModal({ app, action: 'ACCEPTED' })}
              className="rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">✅ Terima</button>
            <button onClick={() => setActionModal({ app, action: 'REJECTED' })}
              className="rounded-lg bg-gradient-to-r from-red-500 to-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">❌ Tolak</button>
          </>
        );
      case 'REVIEWED':
        return (
          <>
            <button onClick={() => { setInterviewTarget(app); setShowInterviewModal(true); }}
              className="rounded-lg border border-purple-300 bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-700 transition-all hover:bg-purple-100">🗓️ Interview</button>
            <button onClick={() => setActionModal({ app, action: 'ACCEPTED' })}
              className="rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">✅ Terima</button>
            <button onClick={() => setActionModal({ app, action: 'REJECTED' })}
              className="rounded-lg bg-gradient-to-r from-red-500 to-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">❌ Tolak</button>
          </>
        );
      case 'INTERVIEW':
        return (
          <>
            <button onClick={() => setActionModal({ app, action: 'ACCEPTED' })}
              className="rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">✅ Terima</button>
            <button onClick={() => setActionModal({ app, action: 'REJECTED' })}
              className="rounded-lg bg-gradient-to-r from-red-500 to-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">❌ Tolak</button>
          </>
        );
      default: return null;
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Pelamar</h1>
        <p className="text-sm text-slate-500">Tinjau dan kelola pelamar untuk lowongan Anda.</p>
      </div>

      {/* Results */}
      {loading ? (
        <LoadingState text="Memuat pelamar..." />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !apps.length ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl">
          <EmptyState title="Belum ada pelamar" description="Pelamar akan muncul di sini setelah siswa mengirim lamaran." icon="👥" />
        </div>
      ) : (
        <>
          <div className="space-y-3">
            {apps.map((app) => (
              <div key={app.id} className="group overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 p-5 shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-blue-300 hover:shadow-lg hover:shadow-slate-200/50">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-start gap-4 min-w-0 flex-1">
                    <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 text-sm font-bold text-white shadow-md shadow-purple-200 transition-transform duration-300 group-hover:scale-110">
                      {(app.student?.user?.name || 'S').charAt(0)}
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="text-sm font-bold text-slate-900 group-hover:text-blue-700">{app.student?.user?.name || '-'}</h3>
                      <p className="text-xs text-slate-500">{app.internship?.title}</p>
                      <p className="mt-1 text-xs text-slate-400">Dilamar: {app.applied_at ? new Date(app.applied_at).toLocaleDateString('id-ID') : '-'}</p>
                      {app.attachments?.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          {app.attachments.map((att) => (
                            <a key={att.id} href={att.download_url} target="_blank" rel="noopener noreferrer"
                              className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 transition-all hover:bg-blue-50 hover:text-blue-700">
                              📎 {att.type}
                            </a>
                          ))}
                        </div>
                      )}
                      {app.message && (
                        <p className="mt-2 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-600 italic line-clamp-2 border border-slate-200/60">"{app.message}"</p>
                      )}
                    </div>
                  </div>
                  <span className={`flex-shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${statusColors[app.status] || 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                    {statusLabels[app.status] || app.status}
                  </span>
                </div>

                <div className="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                  <button onClick={() => handleViewDetail(app)}
                    className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-all hover:bg-slate-50">
                    👁️ Detail
                  </button>
                  {getActionButtons(app)}
                </div>
              </div>
            ))}
          </div>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </>
      )}

      {/* Detail Modal */}
      <Modal open={!!selectedApp} onClose={() => { setSelectedApp(null); setDetailData(null); }} title="Detail Pelamar" maxWidth="max-w-lg">
        {detailLoading ? (
          <LoadingState text="Memuat detail..." />
        ) : detailData && (
          <div className="space-y-4 max-h-[70vh] overflow-y-auto">
            {/* Student Profile */}
            <div>
              <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">👤 Profil Siswa</h4>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              {[
                { label: 'Nama', value: detailData.student?.user?.name || detailData.student?.name },
                { label: 'NIS', value: detailData.student?.nis },
                { label: 'Sekolah', value: detailData.student?.school?.name || detailData.student?.school_name },
                { label: 'Jurusan', value: detailData.student?.major?.name || detailData.student?.major_name },
                { label: 'Kelas', value: detailData.student?.class },
                { label: 'Telepon', value: detailData.student?.phone },
                { label: 'Alamat', value: detailData.student?.address },
                { label: 'Minat/Bio', value: detailData.student?.interests },
              ].filter(i => i.value).map((item) => (
                <div key={item.label} className="rounded-xl bg-slate-50/80 p-3.5">
                  <p className="text-xs font-medium text-slate-500">{item.label}</p>
                  <p className="mt-1 text-sm font-medium text-slate-900">{item.value}</p>
                </div>
              ))}
            </div>

            {/* Skills */}
            {detailData.student?.skills?.length > 0 && (
              <div>
                <p className="text-xs font-medium text-slate-500 mb-1.5">Skill</p>
                <div className="flex flex-wrap gap-1.5">
                  {detailData.student.skills.map((s) => (
                    <span key={s.id} className="rounded-full bg-gradient-to-r from-blue-50 to-indigo-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                      {s.name} {s.level && `(${s.level})`}
                    </span>
                  ))}
                </div>
              </div>
            )}

            {/* Application Info */}
            <div className="border-t border-slate-200 pt-4">
              <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">📋 Lamaran</h4>
            </div>
            <div className="rounded-xl bg-slate-50/80 p-3.5">
              <p className="text-xs font-medium text-slate-500">Lowongan</p>
              <p className="mt-1 text-sm font-medium text-slate-900">{detailData.internship?.title}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500 mb-1">Status</p>
              <span className={`rounded-full px-3 py-1 text-xs font-semibold ${statusColors[detailData.status] || 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                {statusLabels[detailData.status] || detailData.status}
              </span>
            </div>
            {detailData.message && (
              <div className="rounded-xl bg-blue-50 p-4 border border-blue-200/60">
                <p className="text-xs font-medium text-blue-600 mb-1">Deskripsi / Keterangan Lamaran</p>
                <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{detailData.message}</p>
              </div>
            )}

            {/* Attachments */}
            {detailData.attachments?.length > 0 && (
              <div>
                <p className="text-xs font-medium text-slate-500 mb-2">📎 Lampiran File</p>
                <div className="space-y-2">
                  {detailData.attachments.map((att) => (
                    <div key={att.id} className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3.5">
                      <div className="flex items-center gap-3">
                        <span className="text-xl">📄</span>
                        <div>
                          <p className="text-sm font-medium text-slate-900">{att.title}</p>
                          <p className="text-xs text-slate-500">{att.type} · {att.original_name}{att.file_size && ` · ${(att.file_size / 1024).toFixed(1)} KB`}</p>
                        </div>
                      </div>
                      <a href={att.download_url} target="_blank" rel="noopener noreferrer"
                        className="rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">
                        📥 Unduh
                      </a>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Actions */}
            <div className="border-t border-slate-200 pt-4 flex gap-2">
              {getActionButtons(detailData)}
            </div>
          </div>
        )}
      </Modal>

      {/* Accept/Reject with Note Modal */}
      <Modal open={!!actionModal} onClose={() => { setActionModal(null); setActionNote(''); }}
        title={actionModal?.action === 'ACCEPTED' ? '✅ Terima Lamaran' : '❌ Tolak Lamaran'}>
        <div className="space-y-4">
          <p className="text-sm text-slate-700">
            {actionModal?.action === 'ACCEPTED'
              ? `Terima lamaran dari ${actionModal?.app?.student?.user?.name || 'siswa'}?`
              : `Tolak lamaran dari ${actionModal?.app?.student?.user?.name || 'siswa'}?`}
          </p>
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">
              {actionModal?.action === 'ACCEPTED' ? 'Keterangan Penerimaan (opsional)' : 'Alasan Penolakan (opsional)'}
            </label>
            <textarea rows={3}
              placeholder={actionModal?.action === 'ACCEPTED' ? 'contoh: Selamat! Anda diterima di posisi ini...' : 'contoh: Maaf, posisi sudah terpenuhi...'}
              value={actionNote} onChange={(e) => setActionNote(e.target.value)}
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none"
            />
          </div>
          <div className="flex justify-end gap-2">
            <button onClick={() => { setActionModal(null); setActionNote(''); }}
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50">Batal</button>
            <button onClick={handleActionConfirm} disabled={actionSaving}
              className={`rounded-xl px-5 py-2 text-sm font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-50 ${
                actionModal?.action === 'ACCEPTED' ? 'bg-gradient-to-r from-emerald-500 to-teal-600 shadow-emerald-200' : 'bg-gradient-to-r from-red-500 to-rose-600 shadow-red-200'
              }`}>
              {actionSaving ? 'Menyimpan...' : actionModal?.action === 'ACCEPTED' ? 'Ya, Terima' : 'Ya, Tolak'}
            </button>
          </div>
        </div>
      </Modal>

      {/* Interview Scheduling Modal */}
      <Modal open={showInterviewModal} onClose={() => { setShowInterviewModal(false); setInterviewTarget(null); }} title="🗓️ Jadwalkan Interview">
        <div className="space-y-4">
          {interviewTarget && (
            <div className="rounded-xl bg-purple-50 p-3 border border-purple-200">
              <p className="text-xs font-medium text-purple-600">Pelamar</p>
              <p className="text-sm font-semibold text-slate-900">{interviewTarget.student?.user?.name}</p>
            </div>
          )}
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Tanggal & Waktu *</label>
            <input type="datetime-local" value={interviewForm.scheduled_at}
              onChange={(e) => setInterviewForm({ ...interviewForm, scheduled_at: e.target.value })}
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
          </div>
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Mode *</label>
            <select value={interviewForm.mode}
              onChange={(e) => setInterviewForm({ ...interviewForm, mode: e.target.value })}
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
              <option value="onsite">🏢 Onsite</option>
              <option value="online">💻 Online</option>
            </select>
          </div>
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Lokasi / Link</label>
            <input type="text" value={interviewForm.location}
              onChange={(e) => setInterviewForm({ ...interviewForm, location: e.target.value })}
              placeholder="Alamat kantor atau link meeting"
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
          </div>
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Catatan</label>
            <textarea rows={2} value={interviewForm.notes}
              onChange={(e) => setInterviewForm({ ...interviewForm, notes: e.target.value })}
              placeholder="Instruksi untuk pelamar"
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none" />
          </div>
          <div className="flex justify-end gap-2">
            <button onClick={() => { setShowInterviewModal(false); setInterviewTarget(null); }}
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50">Batal</button>
            <button onClick={handleScheduleInterview} disabled={!interviewForm.scheduled_at}
              className="rounded-xl bg-gradient-to-r from-purple-500 to-violet-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-purple-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-purple-300 disabled:opacity-50">
              Jadwalkan
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
