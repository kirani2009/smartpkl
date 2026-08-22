import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useFetch } from '../../hooks/useApi';
import { Card, StatusBadge, Pagination, LoadingState, EmptyState, ErrorState } from '../../components/ui';

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'PENDING', label: 'Pending' },
  { value: 'REVIEW', label: 'Review' },
  { value: 'INTERVIEW', label: 'Interview' },
  { value: 'ACCEPTED', label: 'Diterima' },
  { value: 'REJECTED', label: 'Ditolak' },
];

export default function StudentApplications() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');

  const { data, loading, error, refetch } = useFetch('/student/applications', {
    params: { per_page: 10, page, ...(status ? { status } : {}) },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Lamaran Saya</h1>
        <p className="text-sm text-slate-500">Pantau status lamaran PKL Anda.</p>
      </div>

      {/* Filter */}
      <div className="flex flex-wrap gap-2">
        {statusOptions.map((opt) => (
          <button
            key={opt.value}
            onClick={() => { setStatus(opt.value); setPage(1); }}
            className={`rounded-full px-3 py-1.5 text-xs font-medium transition ${
              status === opt.value ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            {opt.label}
          </button>
        ))}
      </div>

      {loading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !data?.items?.length ? (
        <EmptyState title="Belum ada lamaran" description="Mulai cari lowongan dan kirim lamaran pertama Anda." />
      ) : (
        <>
          <div className="space-y-3">
            {data.items.map((app) => (
              <Card key={app.id}>
                <Card.Body>
                  <div className="flex items-start justify-between">
                    <div>
                      <Link
                        to={`/student/applications/${app.id}`}
                        className="text-sm font-semibold text-slate-900 hover:text-blue-600"
                      >
                        {app.internship?.title}
                      </Link>
                      <p className="mt-0.5 text-xs text-slate-500">
                        {app.internship?.company?.name} · {app.internship?.location}
                      </p>
                      <p className="mt-1 text-xs text-slate-400">
                        Dilamar: {app.applied_at ? new Date(app.applied_at).toLocaleDateString('id-ID') : '-'}
                      </p>
                    </div>
                    <StatusBadge status={app.status} />
                  </div>
                </Card.Body>
              </Card>
            ))}
          </div>
          <Pagination meta={data.meta} onPageChange={setPage} />
        </>
      )}
    </div>
  );
}
