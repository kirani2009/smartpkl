import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useFetch } from '../../hooks/useApi';
import { Pagination, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function StudentInternships() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');

  const { data, loading, error, refetch } = useFetch('/student/internships', {
    params: {
      per_page: 12,
      page,
      ...(query ? { q: query } : {}),
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setQuery(search);
    setPage(1);
  };

  const handleClearSearch = () => {
    setSearch('');
    setQuery('');
    setPage(1);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Cari Lowongan PKL</h1>
        <p className="text-sm text-slate-500">Temukan lowongan praktik kerja yang sesuai dengan minat Anda.</p>
      </div>

      {/* Search Bar */}
      <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-6 shadow-sm backdrop-blur-xl">
        <form onSubmit={handleSearch} className="flex gap-3">
          <div className="relative flex-1">
            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
              <svg className="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              type="text"
              placeholder="Cari berdasarkan jurusan, nama perusahaan, atau posisi..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            />
          </div>
          <button
            type="submit"
            className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300"
          >
            🔍 Cari
          </button>
          {query && (
            <button
              type="button"
              onClick={handleClearSearch}
              className="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition-all hover:bg-slate-50"
            >
              ✕ Reset
            </button>
          )}
        </form>
        {query && (
          <p className="mt-3 text-xs text-slate-500">
            Hasil pencarian untuk: <span className="font-semibold text-blue-700">"{query}"</span>
          </p>
        )}
      </div>

      {/* Results */}
      {loading ? (
        <LoadingState text="Mencari lowongan..." />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !data?.items?.length ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl">
          <EmptyState
            title="Lowongan tidak ditemukan"
            description={query ? `Tidak ada lowongan yang cocok dengan "${query}". Coba kata kunci lain.` : 'Belum ada lowongan tersedia.'}
            icon="🔍"
          />
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {data.items.map((internship) => (
              <Link key={internship.id} to={`/internships/${internship.id}`}>
                <div className="group relative h-full overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 p-6 shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl hover:shadow-slate-200/50">
                  {/* Company Initial */}
                  <div className="mb-4 flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-md shadow-blue-200 transition-transform duration-300 group-hover:scale-110">
                      {(internship.company?.name || 'C').charAt(0)}
                    </div>
                    <div className="min-w-0">
                      <h3 className="truncate text-sm font-bold text-slate-900 group-hover:text-blue-700">{internship.title}</h3>
                      <p className="truncate text-xs text-slate-500">{internship.company?.name}</p>
                    </div>
                  </div>

                  {/* Tags */}
                  <div className="mb-3 flex flex-wrap gap-1.5">
                    {internship.position && (
                      <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                        💼 {internship.position}
                      </span>
                    )}
                    {(internship.required_major || internship.major?.name) && (
                      <span className="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                        🎓 {internship.required_major || internship.major?.name}
                      </span>
                    )}
                    {internship.quota && (
                      <span className="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                        Kuota: {internship.quota}
                      </span>
                    )}
                  </div>

                  {/* Description */}
                  {internship.description && (
                    <p className="text-xs leading-relaxed text-slate-500 line-clamp-2">{internship.description}</p>
                  )}

                  {/* Location */}
                  {internship.location && (
                    <p className="mt-3 text-xs text-slate-400">📍 {internship.location}</p>
                  )}

                  {/* Hover indicator */}
                  <div className="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                </div>
              </Link>
            ))}
          </div>
          <Pagination meta={data.meta} onPageChange={setPage} />
        </>
      )}
    </div>
  );
}
