import { useState } from 'react';
import { useFetch } from '../../hooks/useApi';
import { Card, Table, StatusBadge, Pagination, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function TeacherStudents() {
  const [page, setPage] = useState(1);
  const { data, loading, error, refetch } = useFetch('/teacher/monitoring/students', {
    params: { per_page: 15, page },
  });

  const columns = [
    { key: 'name', label: 'Nama', render: (_, row) => row.user?.name || '-' },
    { key: 'nis', label: 'NIS' },
    { key: 'class', label: 'Kelas' },
    { key: 'major', label: 'Jurusan', render: (v) => v?.name || '-' },
    { key: 'applications_count', label: 'Lamaran' },
    { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v || 'Belum Ditempatkan'} /> },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Daftar Siswa</h1>
        <p className="text-sm text-slate-500">Pantau status siswa sekolah Anda.</p>
      </div>

      <Card>
        {loading ? (
          <LoadingState />
        ) : error ? (
          <ErrorState message={error} onRetry={refetch} />
        ) : !data?.items?.length ? (
          <EmptyState title="Belum ada siswa" description="Siswa akan muncul di sini setelah mendaftar." />
        ) : (
          <>
            <Table columns={columns} data={data.items} emptyMessage="Tidak ada data siswa." />
            <Pagination meta={data.meta} onPageChange={setPage} />
          </>
        )}
      </Card>
    </div>
  );
}
