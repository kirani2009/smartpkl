import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function TeacherPartnerships() {
  const { data: teacherProfile, loading: profileLoading } = useFetch('/me/teacher');
  const { data: partnershipsRes, loading, error, refetch } = useFetch('/teacher/partnerships');
  const { mutate } = useMutation();

  const [actionModal, setActionModal] = useState(null);
  const [responseNotes, setResponseNotes] = useState('');
  const [saving, setSaving] = useState(false);

  const schoolId = teacherProfile?.school?.id || teacherProfile?.school_name;
  const partnerships = partnershipsRes?.items || partnershipsRes || [];

  const handleAction = async () => {
    if (!actionModal) return;
    setSaving(true);
    try {
      const status = actionModal.action === 'accept' ? 'ACCEPTED' : 'REJECTED';
      await mutate('put', `/teacher/partnerships/${actionModal.partnership.id}`, {
        status,
        response_notes: responseNotes.trim() || null,
      });
      setActionModal(null);
      setResponseNotes('');
      refetch();
    } catch {} finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Partnership</h1>
        <p className="text-sm text-slate-500">Kelola pengajuan kerja sama dari perusahaan.</p>
      </div>

      {/* No profile warning */}
      {!schoolId && !profileLoading && (
        <div className="rounded-2xl border border-amber-200/60 bg-gradient-to-r from-amber-50 to-orange-50 p-5 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100">
              <span className="text-xl">⚠️</span>
            </div>
            <div>
              <p className="text-sm font-semibold text-slate-900">Profil guru belum lengkap</p>
              <p className="text-xs text-slate-500">
                Silakan lengkapi profil guru terlebih dahulu.{' '}
                <a href="/teacher/profile" className="font-medium text-blue-600 hover:text-blue-800 transition-colors">Lengkapi Profil →</a>
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Partnership List */}
      <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
        <div className="border-b border-slate-100 px-6 py-4">
          <h2 className="text-sm font-semibold text-slate-900">Pengajuan Partnership</h2>
        </div>
        <div className="p-6">
          {loading ? (
            <LoadingState text="Memuat partnership..." />
          ) : error ? (
            <ErrorState message={error} onRetry={refetch} />
          ) : !partnerships.length ? (
            <EmptyState
              icon="🤝"
              title="Belum ada pengajuan partnership"
              description="Perusahaan akan mengajukan kerja sama kepada sekolah Anda."
            />
          ) : (
            <div className="space-y-4">
              {partnerships.map((p) => (
                <div key={p.id} className="group overflow-hidden rounded-xl border border-slate-200 p-5 transition-all duration-300 hover:border-blue-300 hover:shadow-sm">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-4 min-w-0 flex-1">
                      <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md shadow-violet-200 transition-transform duration-300 group-hover:scale-110">
                        {(p.company?.profile?.name || 'P').charAt(0)}
                      </div>
                      <div className="min-w-0 flex-1">
                        <h3 className="text-sm font-bold text-slate-900">{p.company?.profile?.name || 'Perusahaan'}</h3>
                        {p.company?.profile?.industry && (
                          <p className="text-xs text-slate-500">{p.company.profile.industry}</p>
                        )}

                        {p.company?.profile?.description && (
                          <p className="mt-2 text-xs text-slate-600 line-clamp-2">{p.company.profile.description}</p>
                        )}

                        {p.notes && (
                          <div className="mt-3 rounded-lg bg-blue-50 p-3 border border-blue-200/60">
                            <p className="text-xs font-medium text-blue-600">Deskripsi Pengajuan</p>
                            <p className="mt-1 text-xs text-blue-700 whitespace-pre-line leading-relaxed">{p.notes}</p>
                          </div>
                        )}

                        {p.response_notes && (
                          <div className={`mt-3 rounded-lg p-3 border ${p.status === 'ACCEPTED' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
                            <p className={`text-xs font-medium ${p.status === 'ACCEPTED' ? 'text-emerald-700' : 'text-red-700'}`}>
                              {p.status === 'ACCEPTED' ? '✅ Keterangan Penerimaan' : '❌ Alasan Penolakan'}
                            </p>
                            <p className={`mt-1 text-xs whitespace-pre-line leading-relaxed ${p.status === 'ACCEPTED' ? 'text-emerald-600' : 'text-red-600'}`}>
                              {p.response_notes}
                            </p>
                          </div>
                        )}

                        <p className="mt-2 text-xs text-slate-400">
                          Diajukan: {new Date(p.created_at).toLocaleDateString('id-ID')}
                        </p>
                      </div>
                    </div>

                    <span className={`flex-shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${
                      p.status === 'PENDING' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                      p.status === 'ACCEPTED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                      p.status === 'REJECTED' ? 'bg-red-50 text-red-700 border border-red-200' :
                      'bg-slate-100 text-slate-600 border border-slate-200'
                    }`}>
                      {p.status === 'PENDING' ? '⏳ Menunggu' :
                       p.status === 'ACCEPTED' ? '✅ Diterima' :
                       p.status === 'REJECTED' ? '❌ Ditolak' : p.status}
                    </span>
                  </div>

                  {p.status === 'PENDING' && (
                    <div className="mt-4 border-t border-slate-100 pt-3 flex gap-2">
                      <button
                        onClick={() => { setActionModal({ partnership: p, action: 'accept' }); setResponseNotes(''); }}
                        className="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-emerald-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-emerald-300"
                      >
                        ✅ Terima
                      </button>
                      <button
                        onClick={() => { setActionModal({ partnership: p, action: 'reject' }); setResponseNotes(''); }}
                        className="rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-red-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-300"
                      >
                        ❌ Tolak
                      </button>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Accept/Reject Modal */}
      {actionModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onClick={() => { setActionModal(null); setResponseNotes(''); }} />
          <div className="relative w-full max-w-md rounded-2xl border border-slate-200/60 bg-white/95 p-6 shadow-2xl backdrop-blur-xl">
            <h3 className="text-lg font-bold text-slate-900">
              {actionModal.action === 'accept' ? '✅ Terima Partnership' : '❌ Tolak Partnership'}
            </h3>
            <p className="mt-2 text-sm text-slate-600">
              {actionModal.action === 'accept'
                ? `Terima pengajuan dari ${actionModal.partnership?.company?.profile?.name || 'perusahaan'}?`
                : `Tolak pengajuan dari ${actionModal.partnership?.company?.profile?.name || 'perusahaan'}?`}
            </p>
            <div className="mt-4">
              <label className="mb-1.5 block text-xs font-medium text-slate-600">
                {actionModal.action === 'accept' ? 'Keterangan Penerimaan *' : 'Alasan Penolakan *'}
              </label>
              <textarea
                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none"
                rows={4}
                placeholder={actionModal.action === 'accept'
                  ? 'contoh: Kami setujui kerja sama ini...'
                  : 'contoh: Maaf, kuota sudah penuh...'}
                value={responseNotes}
                onChange={(e) => setResponseNotes(e.target.value)}
                required
              />
            </div>
            <div className="mt-4 flex justify-end gap-2">
              <button
                onClick={() => { setActionModal(null); setResponseNotes(''); }}
                className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
              >
                Batal
              </button>
              <button
                onClick={handleAction}
                disabled={saving || !responseNotes.trim()}
                className={`rounded-xl px-5 py-2 text-sm font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed ${
                  actionModal.action === 'accept'
                    ? 'bg-gradient-to-r from-emerald-500 to-teal-600 shadow-emerald-200'
                    : 'bg-gradient-to-r from-red-500 to-rose-600 shadow-red-200'
                }`}
              >
                {saving ? 'Menyimpan...' : actionModal.action === 'accept' ? 'Ya, Terima' : 'Ya, Tolak'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
