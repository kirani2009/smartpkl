import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { LoadingState, EmptyState, ErrorState } from '../../components/ui';
import api from '../../api/api';

export default function TeacherStudents() {
  const [selectedMajor, setSelectedMajor] = useState(null);
  const [page, setPage] = useState(1);
  const [localSchoolId, setLocalSchoolId] = useState(null);

  const { data: teacherProfile, loading: profileLoading, error: profileError } = useFetch('/me/teacher');
  const schoolId = localSchoolId || teacherProfile?.school?.id;
  const schoolName = teacherProfile?.school_name || teacherProfile?.school?.name;

  const { data: majorsRes, loading: majorsLoading, error: majorsError, refetch: refetchMajors } = useFetch(
    `/schools/${schoolId}/majors`,
    { enabled: !!schoolId }
  );

  const { data: studentsRes, loading: studentsLoading, error: studentsError, refetch } = useFetch(
    '/teacher/monitoring/students',
    {
      params: { per_page: 15, page, ...(selectedMajor ? { major_id: selectedMajor.id } : {}) },
      enabled: !!selectedMajor,
    }
  );

  const students = studentsRes?.items || [];
  const meta = studentsRes?.meta;
  const majors = majorsRes?.items || [];

  const [showAddForm, setShowAddForm] = useState(false);
  const [newMajorName, setNewMajorName] = useState('');
  const [adding, setAdding] = useState(false);
  const [addError, setAddError] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [studentDetail, setStudentDetail] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const { mutate } = useMutation();

  const handleViewStudent = async (studentId) => {
    setLoadingDetail(true);
    setSelectedStudent({ id: studentId });
    setStudentDetail(null);
    try {
      const res = await api.get(`/teacher/monitoring/students/${studentId}`);
      if (res.data?.success) {
        setStudentDetail(res.data.data);
      }
    } catch (err) {
      setStudentDetail({ error: err?.response?.data?.message || 'Gagal memuat detail siswa.' });
    } finally {
      setLoadingDetail(false);
    }
  };

  const handleDeleteMajor = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await mutate('delete', `/majors/${deleteTarget.id}`);
      setDeleteTarget(null);
      if (selectedMajor?.id === deleteTarget.id) {
        setSelectedMajor(null);
      }
      refetchMajors();
    } catch (err) {
      alert(err?.response?.data?.message || 'Gagal menghapus jurusan.');
    } finally {
      setDeleting(false);
    }
  };

  const handleAddMajor = async (e) => {
    e.preventDefault();
    const name = newMajorName.trim();
    if (!name) return;

    if (!schoolId && !schoolName) {
      setAddError('Profil guru belum lengkap. Silakan lengkapi profil terlebih dahulu.');
      return;
    }

    setAdding(true);
    setAddError('');
    try {
      let effectiveSchoolId = schoolId;
      if (!effectiveSchoolId && schoolName) {
        const resolveRes = await api.post('/schools/resolve', { name: schoolName });
        if (!resolveRes.data?.success) throw new Error('Gagal menemukan sekolah.');
        effectiveSchoolId = resolveRes.data.data.id;
        await api.put('/me/teacher', { school_id: effectiveSchoolId, school_name: schoolName });
      }

      await mutate('post', `/schools/${effectiveSchoolId}/majors`, { name });
      setLocalSchoolId(effectiveSchoolId);
      setNewMajorName('');
      setShowAddForm(false);
      refetchMajors();
    } catch (err) {
      const msg = err?.response?.data?.message || err?.response?.data?.errors?.name?.[0] || err?.message || 'Gagal menambah jurusan.';
      setAddError(msg);
    } finally {
      setAdding(false);
    }
  };

  const isLoading = profileLoading || (!!schoolId && majorsLoading);
  const displayError = profileError || majorsError;
  const hasNoData = !isLoading && !displayError && !majors.length;

  // Major list view
  if (!selectedMajor) {
    return (
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">Jurusan & Siswa</h1>
            <p className="text-sm text-slate-500">
              {schoolName ? `Sekolah: ${schoolName}` : 'Lengkapi profil guru terlebih dahulu.'}
            </p>
          </div>
          {(schoolId || schoolName) && (
            <button
              onClick={() => setShowAddForm(true)}
              className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300"
            >
              + Tambah Jurusan
            </button>
          )}
        </div>

        {/* No profile warning */}
        {!schoolId && !schoolName && !profileLoading && (
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

        {/* Add Major Form */}
        {showAddForm && (
          <div className="overflow-hidden rounded-2xl border border-blue-300/60 bg-white/80 shadow-sm backdrop-blur-xl">
            <div className="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
              <h2 className="text-sm font-semibold text-slate-900">📝 Tambah Jurusan Baru</h2>
              <button
                onClick={() => { setShowAddForm(false); setNewMajorName(''); setAddError(''); }}
                className="rounded-lg p-1.5 text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600"
              >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div className="p-6">
              <form onSubmit={handleAddMajor} className="flex gap-3">
                <div className="flex-1">
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Nama Jurusan *</label>
                  <input
                    type="text"
                    value={newMajorName}
                    onChange={(e) => { setNewMajorName(e.target.value); setAddError(''); }}
                    placeholder="contoh: Teknik Informatika, RPL, Akuntansi"
                    required
                    className={`w-full rounded-xl border ${addError ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                  />
                  {addError && <p className="mt-1 text-xs text-red-600">{addError}</p>}
                </div>
                <div className="flex items-end gap-2">
                  <button
                    type="submit"
                    disabled={adding || !newMajorName.trim()}
                    className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {adding ? 'Menyimpan...' : '💾 Simpan'}
                  </button>
                  <button
                    type="button"
                    onClick={() => { setShowAddForm(false); setNewMajorName(''); setAddError(''); }}
                    className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
                  >
                    Batal
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Majors Grid */}
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
          <div className="border-b border-slate-100 px-6 py-4">
            <h2 className="text-sm font-semibold text-slate-900">Daftar Jurusan</h2>
          </div>
          <div className="p-6">
            {isLoading ? (
              <LoadingState text="Memuat data jurusan..." />
            ) : displayError ? (
              <ErrorState message={displayError} onRetry={refetchMajors} />
            ) : hasNoData ? (
              <EmptyState
                icon="📚"
                title="Belum ada jurusan yang diinput."
                description={schoolId ? "Klik 'Tambah Jurusan' untuk membuat jurusan baru." : "Lengkapi profil guru terlebih dahulu."}
              />
            ) : (
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {majors.map((major) => (
                  <div
                    key={major.id}
                    className="group relative overflow-hidden rounded-xl border border-slate-200 p-4 transition-all duration-300 hover:border-blue-300 hover:bg-blue-50/50 hover:shadow-md hover:shadow-slate-200/50 hover:-translate-y-0.5"
                  >
                    <button
                      onClick={() => { setSelectedMajor(major); setPage(1); }}
                      className="flex w-full items-center gap-3 text-left"
                    >
                      <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-md shadow-blue-200 transition-transform duration-300 group-hover:scale-110">
                        {major.name.charAt(0)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-semibold text-slate-900 group-hover:text-blue-700 truncate">{major.name}</p>
                        {major.code && <p className="text-xs text-slate-500">Kode: {major.code}</p>}
                        <p className="text-xs text-slate-400">{(major.students_count ?? 0)} siswa</p>
                      </div>
                      <svg className="h-5 w-5 flex-shrink-0 text-slate-400 transition-all duration-300 group-hover:text-blue-500 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    {/* Delete button */}
                    <button
                      onClick={(e) => { e.stopPropagation(); setDeleteTarget(major); }}
                      className="absolute top-2 right-2 rounded-lg p-1.5 text-slate-400 opacity-0 transition-all duration-200 hover:bg-red-50 hover:text-red-500 group-hover:opacity-100"
                      title="Hapus jurusan"
                    >
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }

  // Student list view
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <button
          onClick={() => { setSelectedMajor(null); setPage(1); }}
          className="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Siswa Jurusan {selectedMajor.name}</h1>
          <p className="text-sm text-slate-500">{schoolName || ''}</p>
        </div>
      </div>

      {/* Student List */}
      {studentsLoading ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
          <LoadingState text="Memuat data siswa..." />
        </div>
      ) : studentsError ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
          <ErrorState message={studentsError} onRetry={refetch} />
        </div>
      ) : !students.length ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl">
          <EmptyState
            icon="👨‍🎓"
            title="Belum ada siswa pada jurusan ini"
            description="Siswa akan muncul di sini setelah mengisi profil dan memilih jurusan ini."
          />
        </div>
      ) : (
        <>
          <div className="space-y-3">
            {students.map((student) => (
              <div key={student.id} onClick={() => handleViewStudent(student.id)} className="group overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 p-5 shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-blue-300 hover:shadow-lg hover:shadow-slate-200/50 cursor-pointer">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-start gap-4 min-w-0 flex-1">
                    <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-md shadow-blue-200 transition-transform duration-300 group-hover:scale-110">
                      {(student.user?.name || 'S').charAt(0)}
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="text-sm font-bold text-slate-900 group-hover:text-blue-700">{student.user?.name || '-'}</h3>
                      <div className="mt-1 flex flex-wrap gap-1.5">
                        {student.nis && (
                          <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                            NIS: {student.nis}
                          </span>
                        )}
                        {(student.major?.name || student.major_name) && (
                          <span className="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                            🎓 {student.major?.name || student.major_name}
                          </span>
                        )}
                        {(student.school_name) && (
                          <span className="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 border border-purple-200">
                            🏫 {student.school_name}
                          </span>
                        )}
                        {student.class && (
                          <span className="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">
                            📚 {student.class}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                  <span className={`flex-shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${
                    student.is_placed
                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                      : 'bg-slate-100 text-slate-600 border border-slate-200'
                  }`}>
                    {student.is_placed ? '✅ Ditempatkan' : '⏳ Belum PKL'}
                  </span>
                </div>
                {student.total_applications > 0 && (
                  <div className="mt-3 border-t border-slate-100 pt-3">
                    <p className="text-xs text-slate-400">📄 {student.total_applications} lamaran dikirim</p>
                  </div>
                )}
              </div>
            ))}
          </div>

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-between rounded-2xl border border-slate-200/60 bg-white/80 px-6 py-4 shadow-sm backdrop-blur-xl">
              <p className="text-xs text-slate-500">
                Halaman {meta.current_page} dari {meta.last_page} · {meta.total} siswa
              </p>
              <div className="flex gap-2">
                <button
                  onClick={() => setPage(Math.max(1, page - 1))}
                  disabled={page <= 1}
                  className="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-all hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ← Sebelumnya
                </button>
                <button
                  onClick={() => setPage(Math.min(meta.last_page, page + 1))}
                  disabled={page >= meta.last_page}
                  className="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-all hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Berikutnya →
                </button>
              </div>
            </div>
          )}
        </>
      )}

      {/* Delete Confirmation Modal */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onClick={() => !deleting && setDeleteTarget(null)} />
          <div className="relative w-full max-w-md rounded-2xl border border-slate-200/60 bg-white/95 p-6 shadow-2xl backdrop-blur-xl">
            <h3 className="text-lg font-bold text-slate-900">🗑️ Hapus Jurusan</h3>
            <p className="mt-2 text-sm text-slate-600">
              Apakah Anda yakin ingin menghapus jurusan <strong>"{deleteTarget.name}"</strong>?
            </p>
            <p className="mt-2 text-xs text-amber-600 bg-amber-50 rounded-lg p-2.5 border border-amber-200">
              ⚠️ Jurusan akan dihapus dari database. Siswa yang sudah memilih jurusan ini tidak akan dihapus, tetapi jurusannya akan kosong dari profil siswa.
            </p>
            <div className="mt-5 flex justify-end gap-2">
              <button
                onClick={() => setDeleteTarget(null)}
                disabled={deleting}
                className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50 disabled:opacity-50"
              >
                Batal
              </button>
              <button
                onClick={handleDeleteMajor}
                disabled={deleting}
                className="rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-red-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {deleting ? 'Menghapus...' : '🗑️ Hapus'}
              </button>
            </div>
          </div>
        </div>
      )}
      {/* Student Detail Modal */}
      {selectedStudent && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onClick={() => { setSelectedStudent(null); setStudentDetail(null); }} />
          <div className="relative w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-2xl border border-slate-200/60 bg-white/95 p-6 shadow-2xl backdrop-blur-xl">
            {loadingDetail ? (
              <div className="py-12 text-center">
                <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                <p className="mt-3 text-sm text-slate-500">Memuat detail siswa...</p>
              </div>
            ) : studentDetail?.error ? (
              <div className="py-8 text-center">
                <p className="text-sm text-red-600">{studentDetail.error}</p>
                <button onClick={() => { setSelectedStudent(null); setStudentDetail(null); }} className="mt-4 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Tutup</button>
              </div>
            ) : studentDetail ? (
              <div className="space-y-5">
                {/* Header */}
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-4">
                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-lg font-bold text-white shadow-lg shadow-blue-200">
                      {(studentDetail.user?.name || studentDetail.name || 'S').charAt(0)}
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-slate-900">{studentDetail.user?.name || studentDetail.name || '-'}</h3>
                      <p className="text-xs text-slate-500">{studentDetail.user?.email || ''}</p>
                    </div>
                  </div>
                  <button onClick={() => { setSelectedStudent(null); setStudentDetail(null); }} className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>

                {/* PKL Status */}
                <div className={`rounded-xl border p-4 ${studentDetail.is_placed ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
                  <div className="flex items-center gap-3">
                    <span className="text-2xl">{studentDetail.is_placed ? '✅' : '⏳'}</span>
                    <div>
                      <p className={`text-sm font-bold ${studentDetail.is_placed ? 'text-emerald-800' : 'text-amber-800'}`}>
                        {studentDetail.is_placed ? 'Sudah Mendapatkan PKL' : 'Belum Mendapatkan PKL'}
                      </p>
                      {studentDetail.is_placed && studentDetail.placement && (
                        <p className="mt-1 text-xs text-emerald-600">
                          📍 {studentDetail.placement.company_name || 'Perusahaan'} — {studentDetail.placement.internship_title || 'Posisi'}.\n                        </p>
                      )}
                      {studentDetail.total_applications > 0 && (
                        <p className="text-xs text-slate-500 mt-1">{studentDetail.total_applications} lamaran dikirim</p>
                      )}
                    </div>
                  </div>
                </div>

                {/* Profile Info */}
                <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                  <h4 className="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-3">Informasi Profil</h4>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    {studentDetail.nis && (
                      <div><p className="text-xs text-slate-400">NIS/NISN</p><p className="font-medium text-slate-800">{studentDetail.nis}</p></div>
                    )}
                    {studentDetail.class && (
                      <div><p className="text-xs text-slate-400">Kelas</p><p className="font-medium text-slate-800">{studentDetail.class}</p></div>
                    )}
                    {studentDetail.gender && (
                      <div><p className="text-xs text-slate-400">Jenis Kelamin</p><p className="font-medium text-slate-800">{studentDetail.gender === 'male' ? 'Laki-laki' : 'Perempuan'}</p></div>
                    )}
                    {studentDetail.phone && (
                      <div><p className="text-xs text-slate-400">No. HP</p><p className="font-medium text-slate-800">{studentDetail.phone}</p></div>
                    )}
                    {studentDetail.school?.name && (
                      <div><p className="text-xs text-slate-400">Sekolah</p><p className="font-medium text-slate-800">{studentDetail.school.name}</p></div>
                    )}
                    {studentDetail.major?.name && (
                      <div><p className="text-xs text-slate-400">Jurusan</p><p className="font-medium text-slate-800">{studentDetail.major.name}</p></div>
                    )}
                    {studentDetail.address && (
                      <div className="col-span-2"><p className="text-xs text-slate-400">Alamat</p><p className="font-medium text-slate-800">{studentDetail.address}</p></div>
                    )}
                    {studentDetail.interests && (
                      <div className="col-span-2"><p className="text-xs text-slate-400">Minat/Bio</p><p className="font-medium text-slate-800">{studentDetail.interests}</p></div>
                    )}
                  </div>
                </div>

                {/* Skills */}
                {studentDetail.skills && studentDetail.skills.length > 0 && (
                  <div>
                    <h4 className="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Skill</h4>
                    <div className="flex flex-wrap gap-1.5">
                      {studentDetail.skills.map((s) => (
                        <span key={s.id} className="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">{s.name}</span>
                      ))}
                    </div>
                  </div>
                )}

                {/* Applications */}
                {studentDetail.applications && studentDetail.applications.length > 0 && (
                  <div>
                    <h4 className="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Riwayat Lamaran</h4>
                    <div className="space-y-2">
                      {studentDetail.applications.map((app) => (
                        <div key={app.id} className="flex items-center justify-between rounded-lg border border-slate-100 bg-white p-3">
                          <div className="min-w-0">
                            <p className="text-sm font-medium text-slate-900 truncate">{app.internship?.title || 'Lowongan'}</p>
                            <p className="text-xs text-slate-500">{app.internship?.company?.profile?.name || app.internship?.company?.name || ''}</p>
                          </div>
                          <span className={`flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                            app.status === 'ACCEPTED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                            app.status === 'REJECTED' ? 'bg-red-50 text-red-700 border border-red-200' :
                            app.status === 'PENDING' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                            'bg-slate-100 text-slate-600 border border-slate-200'
                          }`}>
                            {app.status}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ) : null}
          </div>
        </div>
      )}
    </div>
  );
}
