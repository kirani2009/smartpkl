import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useFetch } from '../../hooks/useApi';
import { Card, Input, Pagination, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function StudentInternships() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');

  const { data, loading, error, refetch } = useFetch('/student/internships', {
    params: { per_page: 12, page, ...(query ? { q: query } : {}) },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setQuery(search);
    setPage(1);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Cari Lowongan PKL</h1>
        <p className="text-sm text-slate-500">Temukan lowongan praktik kerja yang sesuai dengan minat Anda.</p>
      </div>

      {/* Search */}
      <form onSubmit={handleSearch} className="flex gap-2">
        <div className="flex-1">
          <Input
            placeholder="Cari berdasarkan judul, posisi, atau lokasi..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <button
          type="submit"
          className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"
        >
          🔍 Cari
        </button>
      </form>

      {loading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !data?.items?.length ? (
        <EmptyState title="Tidak ada lowongan ditemukan" description="Coba kata kunci lain atau nanti kembali." />
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {data.items.map((internship) => (
              <Link key={internship.id} to={`/internships/${internship.id}`}>
                <Card className="h-full transition hover:-translate-y-1 hover:shadow-md hover:border-brand-300">
                  <Card.Body>
                    <h3 className="font-semibold text-slate-900">{internship.title}</h3>
                    <p className="mt-1 text-sm text-slate-600">{internship.company?.name}</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                      {internship.location && (
                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">📍 {internship.location}</span>
                      )}
                      {internship.major?.name && (
                        <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">{internship.major.name}</span>
                      )}
                      {internship.quota && (
                        <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">Kuota: {internship.quota}</span>
                      )}
                    </div>
                    {internship.description && (
                      <p className="mt-3 text-xs text-slate-500 line-clamp-2">{internship.description}</p>
                    )}
                  </Card.Body>
                </Card>
              </Link>
            ))}
          </div>
          <Pagination meta={data.meta} onPageChange={setPage} />
        </>
      )}
    </div>
  );
}
