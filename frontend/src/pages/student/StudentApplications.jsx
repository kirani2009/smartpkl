import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Pagination, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

const statusOptions = [
  { value: '', label: 'Semua', color: 'slate' },
  { value: 'PENDING', label: 'Pending', color: 'amber' },
  { value: 'REVIEWED', label: 'Review', color: 'blue' },
  { value: 'INTERVIEW', label: 'Interview', color: 'purple' },
  { value: 'ACCEPTED', label: 'Diterima', color: 'emerald' },
  { value: 'REJECTED', label: 'Ditolak', color: 'red' },
  { value: 'CANCELLED', label: 'Dibatalkan', color: 'slate' },
];

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

/** Extract the company note from status_histories for ACCEPTED or REJECTED status. */
function getCompanyNote(app) {
  if (!app.status_histories?.length) return null;
  const match = app.status_histories.find(
    (h) => h.status === 'ACCEPTED' || h.status === 'REJECTED'
  );
  return match?.note || null;
}

export default function StudentApplications() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');
  const [cancelTarget, setCancelTarget] = useState(null);
  const { mutate, loading: mutating } = useMutation();

  const { data, loading, error, refetch } = useFetch('/student/applications', {
    params: { per_page: 10, page, ...(status ? { status } : {}) },
  });

  const handleCancel = async () => {
    if (!cancelTarget) return;
    try {
      await mutate('delete', `/student/applications/${cancelTarget.id}`);
      setCancelTarget(null);
      refetch();
    } catch {
      // error handled by useMutation
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Lamaran Saya</h1>
        <p className="text-sm text-slate-500">Pantau status lamaran PKL Anda.</p>
      </div>

      {/* Filter Pills */}
      <div className="flex flex-wrap gap-2">
        {statusOptions.map((opt) => (
          <button
            key={opt.value}
            onClick={() => { setStatus(opt.value); setPage(1); }}
            className={`rounded-full px-4 py-1.5 text-xs font-semibold transition-all duration-200 ${
              status === opt.value
                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-200'
                : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:border-slate-300'
            }`}
          >
            {opt.label}
          </button>
        ))}
      </div>

      {/* Results */}
      {loading ? (
        <LoadingState text="Memuat lamaran..." />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !data?.items?.length ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl">
          <EmptyState
            title="Belum ada lamaran"
            description="Mulai cari lowongan dan kirim lamaran pertama Anda."
            icon="📋"
          />
        </div>
      ) : (
        <>
          <div className="space-y-3">
            {data.items.map((app) => (
              <div key={app.id} className="group overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 p-5 shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-blue-300 hover:shadow-lg hover:shadow-slate-200/50">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-start gap-4 min-w-0 flex-1">
                    {/* Company Avatar */}
                    <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-md shadow-blue-200 transition-transform duration-300 group-hover:scale-110">
                      {(app.internship?.company?.name || 'C').charAt(0)}
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="text-sm font-bold text-slate-900 group-hover:text-blue-700">{app.internship?.title}</h3>
                      <p className="text-xs text-slate-500">{app.internship?.company?.name} · {app.internship?.location}</p>
                      {app.message && (
                        <p className="mt-1.5 text-xs text-slate-600 italic line-clamp-1">"{app.message}"</p>
                      )}
                      {(() => {
                        const companyNote = getCompanyNote(app);
                        if (!companyNote) return null;
                        const isAccepted = app.status === 'ACCEPTED';
                        return (
                          <div className={`mt-2 rounded-lg px-3 py-2 text-xs leading-relaxed ${
                            isAccepted
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                              : 'bg-red-50 text-red-700 border border-red-200'
                          }`}>
                            <span className="font-semibold">{isAccepted ? '💬 Pesan dari Perusahaan:' : '📝 Alasan Penolakan:'}</span>
                            <p className="mt-0.5">{companyNote}</p>
                          </div>
                        );
                      })()}
                      {app.attachments?.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          {app.attachments.map((att) => (
                            <a
                              key={att.id}
                              href={att.download_url}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 transition-all hover:bg-blue-50 hover:text-blue-700"
                            >
                              📎 {att.type}
                            </a>
                          ))}
                        </div>
                      )}
                      <p className="mt-1.5 text-xs text-slate-400">
                        Dilamar: {app.applied_at ? new Date(app.applied_at).toLocaleDateString('id-ID') : '-'}
                      </p>
                    </div>
                  </div>

                  <div className="flex flex-shrink-0 items-center gap-2">
                    <span className={`rounded-full px-3 py-1 text-xs font-semibold ${statusColors[app.status] || 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                      {statusLabels[app.status] || app.status}
                    </span>
                    {app.status === 'PENDING' && (
                      <button
                        onClick={() => setCancelTarget(app)}
                        className="rounded-lg p-1.5 text-red-500 transition-all hover:bg-red-50"
                        title="Batalkan lamaran"
                      >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
          <Pagination meta={data.meta} onPageChange={setPage} />
        </>
      )}

      {/* Cancel Confirmation Modal */}
      <Modal open={!!cancelTarget} onClose={() => setCancelTarget(null)} title="Batalkan Lamaran">
        <div className="space-y-4">
          <div className="flex items-center gap-3 rounded-xl bg-red-50 p-4 border border-red-200">
            <span className="text-2xl">⚠️</span>
            <div>
              <p className="text-sm font-medium text-red-800">Anda yakin ingin membatalkan?</p>
              <p className="text-xs text-red-600">Lamaran ke <strong>{cancelTarget?.internship?.title}</strong> akan dibatalkan secara permanen.</p>
            </div>
          </div>
          <p className="text-xs text-slate-500">Lamaran hanya bisa dibatalkan jika status masih Pending.</p>
          <div className="flex justify-end gap-2">
            <button
              onClick={() => setCancelTarget(null)}
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
            >
              Tidak, Kembali
            </button>
            <button
              onClick={handleCancel}
              disabled={mutating}
              className="rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-red-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-300 disabled:opacity-50"
            >
              {mutating ? 'Membatalkan...' : 'Ya, Batalkan'}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
