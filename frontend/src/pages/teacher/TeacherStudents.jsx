import { useState } from 'react';
import { useFetch } from '../../hooks/useApi';
import { Card, Button, Table, StatusBadge, Pagination, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function TeacherStudents() {
  const [selectedMajor, setSelectedMajor] = useState(null);
  const [page, setPage] = useState(1);

  // Fetch majors for the teacher's school
  const { data: dashboard, loading: dashLoading, error: dashError } = useFetch('/teacher/dashboard');

  // Fetch students filtered by major
  const { data: studentsRes, loading: studentsLoading, error: studentsError, refetch } = useFetch(
    '/teacher/monitoring/students',
    {
      params: {
        per_page: 15,
        page,
        ...(selectedMajor ? { major_id: selectedMajor.id } : {}),
      },
      enabled: !!selectedMajor,
    }
  );

  const students = studentsRes?.items || [];
  const meta = studentsRes?.meta;

  // Get school info from dashboard
  const school = dashboard?.school;

  // If no major selected, show list of majors
  if (!selectedMajor) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Daftar Siswa</h1>
          <p className="text-sm text-slate-500">
            {school?.name ? `Sekolah: ${school.name}` : 'Pilih jurusan untuk melihat daftar siswa.'}
          </p>
        </div>

        <Card>
          <Card.Header>
            <h2 className="font-semibold text-slate-900">Pilih Jurusan</h2>
          </Card.Header>
          <Card.Body>
            {dashLoading ? (
              <LoadingState text="Memuat data jurusan..." />
            ) : dashError ? (
              <ErrorState message={dashError} />
            ) : (
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {/* We need to get majors from somewhere. Let's use the students endpoint without major_id to get available majors */}
                <MajorGrid onSelect={setSelectedMajor} schoolId={school?.id} />
              </div>
            )}
          </Card.Body>
        </Card>
      </div>
    );
  }

  // Show students for selected major
  const columns = [
    { key: 'name', label: 'Nama', render: (_, row) => row.user?.name || '-' },
    { key: 'nis', label: 'NIS' },
    { key: 'class', label: 'Kelas' },
    { key: 'applications_count', label: 'Lamaran', render: (_, row) => row.total_applications || 0 },
    { key: 'is_placed', label: 'Status PKL', render: (v) => <StatusBadge status={v ? 'Ditempatkan' : 'Belum Ditempatkan'} /> },
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="secondary" size="sm" onClick={() => { setSelectedMajor(null); setPage(1); }}>
          ← Kembali
        </Button>
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Siswa Jurusan {selectedMajor.name}</h1>
          <p className="text-sm text-slate-500">{school?.name || ''}</p>
        </div>
      </div>

      <Card>
        {studentsLoading ? (
          <LoadingState />
        ) : studentsError ? (
          <ErrorState message={studentsError} onRetry={refetch} />
        ) : !students.length ? (
          <EmptyState title="Belum ada siswa" description="Belum ada siswa pada jurusan ini." />
        ) : (
          <>
            <Table columns={columns} data={students} emptyMessage="Tidak ada data siswa." />
            <Pagination meta={meta} onPageChange={setPage} />
          </>
        )}
      </Card>
    </div>
  );
}

/**
 * MajorGrid — fetches all students without major filter to discover available majors,
 * then groups them by major.
 */
function MajorGrid({ onSelect }) {
  const { data: studentsRes, loading, error } = useFetch('/teacher/monitoring/students', {
    params: { per_page: 1000 },
  });

  if (loading) return <LoadingState text="Memuat data jurusan..." />;
  if (error) return <ErrorState message={error} />;

  const students = studentsRes?.items || [];

  // Group students by major
  const majorMap = {};
  students.forEach((s) => {
    const majorId = s.major?.id;
    const majorName = s.major?.name;
    if (majorId && majorName && !majorMap[majorId]) {
      majorMap[majorId] = { id: majorId, name: majorName, count: 0 };
    }
    if (majorId) {
      majorMap[majorId].count++;
    }
  });

  const majors = Object.values(majorMap);

  if (!majors.length) {
    return <EmptyState title="Belum ada jurusan" description="Belum ada data siswa dengan jurusan." />;
  }

  return (
    <>
      {majors.map((major) => (
        <button
          key={major.id}
          onClick={() => onSelect(major)}
          className="flex items-center justify-between rounded-lg border border-slate-200 p-4 text-left transition hover:border-brand-300 hover:bg-brand-50"
        >
          <div>
            <p className="text-sm font-semibold text-slate-900">{major.name}</p>
            <p className="text-xs text-slate-500">{major.count} siswa</p>
          </div>
          <span className="text-slate-400">→</span>
        </button>
      ))}
    </>
  );
}
