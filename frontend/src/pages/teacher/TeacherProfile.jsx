import { useState, useEffect } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { LoadingState, ErrorState } from '../../components/ui';
import { useAuth } from '../../contexts/AuthContext';

export default function TeacherProfile() {
  const { refreshUser } = useAuth();

  const {
    data: teacherProfile,
    loading: profileLoading,
    error: profileError,
    refetch: refetchProfile,
  } = useFetch('/me/teacher');

  const { mutate, loading: saving } = useMutation();

  const [form, setForm] = useState({
    teacher_name: '',
    school_name: '',
    nip: '',
    position: '',
    phone: '',
  });
  const [successMsg, setSuccessMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');
  const [initialized, setInitialized] = useState(false);

  const isEditing = !!teacherProfile?.school?.id || !!teacherProfile?.school_name;

  useEffect(() => {
    if (teacherProfile && !initialized) {
      setForm({
        teacher_name: teacherProfile.teacher_name || teacherProfile.user?.name || '',
        school_name: teacherProfile.school_name || teacherProfile.school?.name || '',
        nip: teacherProfile.nip || '',
        position: teacherProfile.position || '',
        phone: teacherProfile.phone || '',
      });
      setInitialized(true);
    }
  }, [teacherProfile, initialized]);

  const handleChange = (field, value) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    setErrorMsg('');
    setSuccessMsg('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    if (!form.teacher_name.trim()) {
      setErrorMsg('Nama Guru wajib diisi.');
      return;
    }
    if (!form.school_name.trim()) {
      setErrorMsg('Nama Sekolah wajib diisi.');
      return;
    }

    const payload = {
      teacher_name: form.teacher_name.trim(),
      school_name: form.school_name.trim(),
      nip: form.nip || null,
      position: form.position || null,
      phone: form.phone || null,
    };

    try {
      if (isEditing) {
        await mutate('put', '/me/teacher', payload);
      } else {
        await mutate('post', '/me/teacher', payload);
      }
      setSuccessMsg('Profil guru berhasil disimpan.');
      refetchProfile();
      refreshUser();
    } catch (err) {
      const msg =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.teacher_name?.[0] ||
        err?.response?.data?.errors?.school_name?.[0] ||
        'Gagal menyimpan profil.';
      setErrorMsg(msg);
    }
  };

  if (profileLoading) return <LoadingState text="Memuat profil..." />;
  if (profileError && !initialized) return <ErrorState message={profileError} onRetry={refetchProfile} />;

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Profil Guru</h1>
        <p className="text-sm text-slate-500">
          {isEditing
            ? `${form.teacher_name || 'Guru'} — ${form.school_name || '-'}`
            : 'Lengkapi profil Anda terlebih dahulu.'}
        </p>
      </div>

      {/* Profile Form */}
      <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-8 shadow-sm backdrop-blur-xl">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Success/Error Messages */}
          {successMsg && (
            <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
              <span>✅</span> {successMsg}
            </div>
          )}
          {errorMsg && (
            <div className="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
              <span>⚠️</span> {errorMsg}
            </div>
          )}

          {/* Teacher Identity */}
          <div className="rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-5 border border-blue-200/60">
            <h3 className="mb-4 text-sm font-semibold text-slate-900">👤 Identitas Guru</h3>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Nama Guru *</label>
                <input
                  type="text"
                  value={form.teacher_name}
                  onChange={(e) => handleChange('teacher_name', e.target.value)}
                  placeholder="Nama lengkap guru"
                  required
                  className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Sekolah *</label>
                <input
                  type="text"
                  value={form.school_name}
                  onChange={(e) => handleChange('school_name', e.target.value)}
                  placeholder="contoh: SMKN 1 Jakarta"
                  required
                  className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>
            </div>
          </div>

          {/* Additional Info */}
          <div className="rounded-xl bg-slate-50/80 p-5 border border-slate-200/60">
            <h3 className="mb-4 text-sm font-semibold text-slate-900">📋 Informasi Tambahan</h3>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">NIP</label>
                <input
                  type="text"
                  value={form.nip}
                  onChange={(e) => handleChange('nip', e.target.value)}
                  placeholder="Nomor Induk Pegawai"
                  className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Jabatan</label>
                <input
                  type="text"
                  value={form.position}
                  onChange={(e) => handleChange('position', e.target.value)}
                  placeholder="contoh: Guru Produktif"
                  className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>
            </div>
            <div className="mt-4">
              <label className="mb-1.5 block text-xs font-medium text-slate-600">No. Telepon</label>
              <input
                type="text"
                value={form.phone}
                onChange={(e) => handleChange('phone', e.target.value)}
                placeholder="contoh: 081234567890"
                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              />
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-3 pt-2">
            <button
              type="submit"
              disabled={saving}
              className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {saving ? (
                <span className="flex items-center gap-2">
                  <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                  Menyimpan...
                </span>
              ) : '💾 Simpan Profil'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
