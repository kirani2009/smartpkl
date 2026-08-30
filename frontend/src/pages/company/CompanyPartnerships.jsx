import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

const statusColors = {
  PENDING: 'bg-amber-50 text-amber-700 border border-amber-200',
  ACCEPTED: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
  REJECTED: 'bg-red-50 text-red-700 border border-red-200',
};

const statusLabels = {
  PENDING: '⏳ Menunggu',
  ACCEPTED: '✅ Diterima',
  REJECTED: '❌ Ditolak',
};

export default function CompanyPartnerships() {
  const { data: partnershipsRes, loading, error, refetch } = useFetch('/company/partnerships');
  const { mutate } = useMutation();

  const [showForm, setShowForm] = useState(false);
  const [notes, setNotes] = useState('');
  const [schoolName, setSchoolName] = useState('');
  const [teacherName, setTeacherName] = useState('');
  const [proposing, setProposing] = useState(false);
  const [proposeError, setProposeError] = useState('');
  const [proposeSuccess, setProposeSuccess] = useState('');
  const [cancelTarget, setCancelTarget] = useState(null);
  const [cancelling, setCancelling] = useState(false);

  const partnerships = partnershipsRes?.items || partnershipsRes || [];
  const hasActiveOrPending = partnerships.some((p) => p.status === 'ACCEPTED' || p.status === 'PENDING');

  const handlePropose = async (e) => {
    e.preventDefault();
    if (!notes.trim()) { setProposeError('Deskripsi kerja sama harus diisi.'); return; }
    setProposing(true);
    setProposeError('');
    setProposeSuccess('');
    try {
      const payload = { notes: notes.trim() };
      if (schoolName.trim()) payload.school_name = schoolName.trim();
      if (teacherName.trim()) payload.teacher_name = teacherName.trim();
      await mutate('post', '/company/partnerships', payload);
      setShowForm(false);
      setNotes('');
      setSchoolName('');
      setTeacherName('');
      setProposeSuccess('Pengajuan partnership berhasil dikirim! Menunggu persetujuan guru.');
      refetch();
    } catch (err) {
      setProposeError(err?.response?.data?.message || 'Gagal mengirim pengajuan.');
    } finally { setProposing(false); }
  };

  const handleCancel = async () => {
    if (!cancelTarget) return;
    setCancelling(true);
    try {
      await mutate('delete', `/company/partnerships/${cancelTarget.id}`);
      setCancelTarget(null);
      refetch();
    } catch { /* handled */ }
    finally { setCancelling(false); }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Partnership</h1>
          <p className="text-sm text-slate-500">Ajukan kerja sama dengan sekolah untuk menerima siswa PKL.</p>
        </div>
        {!loading && !hasActiveOrPending && !showForm && (
          <button
            onClick={() => { setShowForm(true); setProposeError(''); setProposeSuccess(''); }}
            className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300"
          >
            + Ajukan Partnership
          </button>
        )}
      </div>

      {/* Success Message */}
      {proposeSuccess && (
        <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
          <span>✅</span> {proposeSuccess}
        </div>
      )}

      {/* Info Box */}
      <div className="rounded-2xl border border-blue-200/60 bg-gradient-to-r from-blue-50 to-indigo-50 p-5 shadow-sm">
        <div className="flex items-start gap-3">
          <span className="text-2xl">💡</span>
          <div>
            <p className="text-sm font-semibold text-slate-900">Cara Kerja Partnership</p>
            <p className="mt-1 text-xs text-slate-600 leading-relaxed">
              Ajukan kerja sama dengan sekolah. Guru dari sekolah tersebut akan menerima atau menolak pengajuan ini.
              Jika diterima, Anda dapat mulai membuat lowongan PKL untuk siswa sekolah tersebut.
            </p>
          </div>
        </div>
      </div>

      {/* Inline Propose Form */}
      {showForm && (
        <div className="overflow-hidden rounded-2xl border border-blue-300/60 bg-white/80 shadow-sm backdrop-blur-xl">
          <div className="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-900">📝 Ajukan Partnership</h2>
            <button onClick={() => { setShowForm(false); setProposeError(''); }}
              className="rounded-lg p-1.5 text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600">
              <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div className="p-6">
            <form onSubmit={handlePropose} className="space-y-4">
              {proposeError && (
                <div className="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                  <span>⚠️</span> {proposeError}
                </div>
              )}

              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Nama Sekolah</label>
                  <input
                    type="text"
                    placeholder="contoh: SMKN 1 Jakarta"
                    value={schoolName}
                    onChange={(e) => setSchoolName(e.target.value)}
                    className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Guru Tujuan</label>
                  <input
                    type="text"
                    placeholder="contoh: Pak Budi Santoso"
                    value={teacherName}
                    onChange={(e) => setTeacherName(e.target.value)}
                    className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  />
                </div>
              </div>

              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Deskripsi / Keterangan Kerja Sama *</label>
                <p className="mb-2 text-xs text-slate-400">Jelaskan tujuan dan bentuk kerja sama yang diinginkan.</p>
                <textarea
                  rows={4} required
                  placeholder="contoh: Kami ingin menjalin kerja sama dalam program Praktik Kerja Lapangan (PKL) untuk siswa jurusan Teknik Komputer dan Jaringan..."
                  value={notes}
                  onChange={(e) => { setNotes(e.target.value); setProposeError(''); }}
                  className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none"
                />
              </div>

              <div className="flex justify-end gap-2">
                <button type="button" onClick={() => { setShowForm(false); setProposeError(''); }}
                  className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50">Batal</button>
                <button type="submit" disabled={proposing || !notes.trim()}
                  className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  {proposing ? 'Mengirim...' : '📨 Kirim Pengajuan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Partnership List */}
      <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
        <div className="border-b border-slate-100 px-6 py-4">
          <h2 className="text-sm font-semibold text-slate-900">Daftar Partnership</h2>
        </div>
        <div className="p-6">
          {loading ? (
            <LoadingState text="Memuat partnership..." />
          ) : error ? (
            <ErrorState message={error} onRetry={refetch} />
          ) : !partnerships.length ? (
            <EmptyState
              title="Belum ada partnership"
              description="Ajukan kerja sama dengan sekolah untuk mulai menerima siswa PKL."
              icon="🤝"
            />
          ) : (
            <div className="space-y-4">
              {partnerships.map((p) => (
                <div key={p.id} className="group overflow-hidden rounded-xl border border-slate-200 p-5 transition-all duration-300 hover:border-blue-300 hover:shadow-sm">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-4 min-w-0 flex-1">
                      <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-md shadow-blue-200">
                        {(p.school?.name || 'S').charAt(0)}
                      </div>
                      <div className="min-w-0 flex-1">
                        <h3 className="text-sm font-bold text-slate-900">{p.school?.name || 'Sekolah'}</h3>
                        {p.school?.city && <p className="text-xs text-slate-500">📍 {p.school.city}</p>}

                        {p.notes && (
                          <div className="mt-2.5 rounded-lg bg-slate-50 p-3 border border-slate-200/60">
                            <p className="text-xs font-medium text-slate-500">Deskripsi Kerja Sama:</p>
                            <p className="mt-1 text-xs text-slate-600 italic whitespace-pre-line leading-relaxed">{p.notes}</p>
                          </div>
                        )}

                        <p className="mt-2 text-xs text-slate-400">
                          Diajukan: {new Date(p.created_at).toLocaleDateString('id-ID')}
                        </p>

                        {/* Status Notes */}
                        {p.response_notes && (
                          <div className={`mt-2.5 rounded-lg p-3 border ${p.status === 'ACCEPTED' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'}`}>
                            <p className={`text-xs font-medium ${p.status === 'ACCEPTED' ? 'text-emerald-700' : 'text-red-700'}`}>
                              {p.status === 'ACCEPTED' ? '✅ Keterangan dari Guru:' : '❌ Alasan Penolakan:'}
                            </p>
                            <p className={`mt-1 text-xs italic whitespace-pre-line leading-relaxed ${p.status === 'ACCEPTED' ? 'text-emerald-600' : 'text-red-600'}`}>
                              {p.response_notes}
                            </p>
                          </div>
                        )}
                      </div>
                    </div>

                    <div className="flex flex-shrink-0 items-center gap-2">
                      <span className={`rounded-full px-3 py-1 text-xs font-semibold ${statusColors[p.status] || 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                        {statusLabels[p.status] || p.status}
                      </span>
                      {p.status === 'PENDING' && (
                        <button onClick={() => setCancelTarget(p)}
                          className="rounded-lg p-1.5 text-red-500 transition-all hover:bg-red-50" title="Batalkan pengajuan">
                          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Cancel Confirmation Modal */}
      <Modal open={!!cancelTarget} onClose={() => setCancelTarget(null)} title="Batalkan Pengajuan">
        <div className="space-y-4">
          <div className="flex items-center gap-3 rounded-xl bg-red-50 p-4 border border-red-200">
            <span className="text-2xl">⚠️</span>
            <div>
              <p className="text-sm font-medium text-red-800">Batalkan pengajuan?</p>
              <p className="text-xs text-red-600">Pengajuan ke <strong>{cancelTarget?.school?.name || 'sekolah'}</strong> akan dibatalkan.</p>
            </div>
          </div>
          <p className="text-xs text-slate-500">Pengajuan yang sudah dibatalkan tidak dapat dikembalikan.</p>
          <div className="flex justify-end gap-2">
            <button onClick={() => setCancelTarget(null)}
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50">Tidak, Kembali</button>
            <button onClick={handleCancel} disabled={cancelling}
              className="rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-red-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-300 disabled:opacity-50">
              {cancelling ? 'Membatalkan...' : 'Ya, Batalkan'}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
