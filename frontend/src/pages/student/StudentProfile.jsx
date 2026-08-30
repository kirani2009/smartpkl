import { useState, useEffect } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Input, Select, Button, LoadingState, ErrorState } from '../../components/ui';

export default function StudentProfile() {
  const { data: profile, loading, error, refetch } = useFetch('/me/student');
  const { mutate } = useMutation();

  const [editing, setEditing] = useState(false);
  const [form, setForm] = useState({});
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');
  const [errors, setErrors] = useState({});

  const isNewStudent = error && (error.includes('belum dibuat') || error.includes('belum lengkap'));

  useEffect(() => {
    if (profile) {
      setForm({
        name: profile.name || profile.user?.name || '',
        nis: profile.nis || '',
        class: profile.class || '',
        entry_year: profile.entry_year || '',
        gender: profile.gender || '',
        birth_date: profile.birth_date || '',
        phone: profile.phone || '',
        address: profile.address || '',
        interests: profile.interests || '',
        school_name: profile.school_name || profile.school?.name || '',
        major_name: profile.major_name || profile.major?.name || '',
      });
    }
  }, [profile]);

  useEffect(() => {
    if (isNewStudent && !editing) setEditing(true);
  }, [isNewStudent, editing]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => ({ ...prev, [name]: '' }));
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMsg('');
    setErrors({});

    const payload = {
      name: form.name || null,
      school_name: form.school_name || null,
      major_name: form.major_name || null,
      nis: form.nis || null,
      class: form.class || null,
      entry_year: form.entry_year || null,
      gender: form.gender || null,
      birth_date: form.birth_date || null,
      phone: form.phone || null,
      address: form.address || null,
      interests: form.interests || null,
    };

    try {
      const method = profile ? 'put' : 'post';
      const res = await mutate(method, '/me/student', payload);
      if (res?.success) {
        setMsg('Profil berhasil disimpan!');
        setEditing(false);
        refetch();
      }
    } catch (err) {
      const validationErrors = err?.response?.data?.errors;
      if (validationErrors) {
        const fe = {};
        for (const [key, msgs] of Object.entries(validationErrors)) {
          fe[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        }
        setErrors(fe);
        setMsg('Mohon periksa kembali data yang diisi.');
      } else {
        setMsg(err?.response?.data?.message || 'Gagal menyimpan profil.');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error && !isNewStudent) return <ErrorState message={error} onRetry={refetch} />;

  const completeness = profile?.profile_completeness ?? 0;
  const missingFields = profile?.missing_fields ?? [];
  const displaySchool = profile?.school_name || profile?.school?.name || '-';
  const displayMajor = profile?.major_name || profile?.major?.name || '-';

  const completenessColor =
    completeness >= 80 ? 'from-emerald-500 to-teal-600' :
    completeness >= 50 ? 'from-amber-500 to-orange-600' : 'from-red-500 to-rose-600';

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Profil Saya</h1>
          <p className="text-sm text-slate-500">
            {isNewStudent ? 'Lengkapi profil Anda untuk mulai melamar PKL.' : 'Kelola informasi profil Anda di sini.'}
          </p>
        </div>
        {!editing && profile && (
          <button
            onClick={() => setEditing(true)}
            className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300"
          >
            ✏️ Edit Profil
          </button>
        )}
      </div>

      {/* Success / Error Messages */}
      {msg && (
        <div className={`flex items-center gap-3 rounded-xl border p-4 text-sm font-medium ${
          msg.includes('berhasil')
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'border-red-200 bg-red-50 text-red-700'
        }`}>
          <span>{msg.includes('berhasil') ? '✅' : '⚠️'}</span>
          {msg}
        </div>
      )}

      {/* Completeness Indicator */}
      {profile && completeness < 100 && !editing && (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-6 shadow-sm backdrop-blur-xl">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium text-slate-700">Kelengkapan Profil</span>
            <span className={`text-sm font-bold bg-gradient-to-r ${completenessColor} bg-clip-text text-transparent`}>{completeness}%</span>
          </div>
          <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div
              className={`h-full rounded-full bg-gradient-to-r transition-all duration-500 ${completenessColor}`}
              style={{ width: `${completeness}%` }}
            />
          </div>
          {missingFields.length > 0 && (
            <div className="mt-3 flex flex-wrap gap-1.5">
              {missingFields.map((field) => (
                <span key={field} className="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">
                  {field}
                </span>
              ))}
            </div>
          )}
        </div>
      )}

      {/* View Mode */}
      {!editing && profile && (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
          {/* Avatar Section */}
          <div className="border-b border-slate-100 px-8 py-6">
            <div className="flex items-center gap-5">
              <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-xl font-bold text-white shadow-lg shadow-blue-200">
                {(profile.name || profile.user?.name || 'S').charAt(0)}
              </div>
              <div>
                <h2 className="text-lg font-bold text-slate-900">{profile.name || profile.user?.name || '-'}</h2>
                <p className="text-sm text-slate-500">{profile.user?.email || '-'}</p>
                <div className="mt-1 flex items-center gap-2">
                  <span className="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">{displaySchool}</span>
                  <span className="rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 border border-purple-200">{displayMajor}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Details Grid */}
          <div className="p-8">
            <div className="grid gap-5 sm:grid-cols-2">
              {[
                { label: 'Nama Siswa', value: profile.name || profile.user?.name },
                { label: 'NIS/NISN', value: profile.nis },
                { label: 'Kelas', value: profile.class },
                { label: 'Jenis Kelamin', value: profile.gender === 'male' ? 'Laki-laki' : profile.gender === 'female' ? 'Perempuan' : null },
                { label: 'Tahun Masuk', value: profile.entry_year },
                { label: 'Telepon', value: profile.phone },
                { label: 'Tanggal Lahir', value: profile.birth_date ? new Date(profile.birth_date).toLocaleDateString('id-ID') : null },
              ].map((item) => (
                <div key={item.label} className="rounded-xl bg-slate-50/80 p-3.5">
                  <p className="text-xs font-medium text-slate-500">{item.label}</p>
                  <p className="mt-1 text-sm font-medium text-slate-900">{item.value || '-'}</p>
                </div>
              ))}
            </div>

            <div className="mt-5 space-y-4">
              <div className="rounded-xl bg-slate-50/80 p-3.5">
                <p className="text-xs font-medium text-slate-500">Alamat Rumah</p>
                <p className="mt-1 text-sm font-medium text-slate-900">{profile.address || '-'}</p>
              </div>
              <div className="rounded-xl bg-slate-50/80 p-3.5">
                <p className="text-xs font-medium text-slate-500">Deskripsi / Minat / Bio</p>
                <p className="mt-1 text-sm font-medium text-slate-900 whitespace-pre-line">{profile.interests || '-'}</p>
              </div>
            </div>

            {profile.skills?.length > 0 && (
              <div className="mt-5">
                <p className="text-xs font-medium text-slate-500 mb-2">Skill</p>
                <div className="flex flex-wrap gap-1.5">
                  {profile.skills.map((skill) => (
                    <span key={skill.id} className="rounded-full bg-gradient-to-r from-blue-50 to-indigo-50 px-3 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                      {skill.name} {skill.level && `(${skill.level})`}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Edit Mode */}
      {editing && (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-8 shadow-sm backdrop-blur-xl">
          <form onSubmit={handleSave} className="space-y-5">
            {/* School & Major */}
            <div className="rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-5 border border-blue-200/60">
              <h3 className="mb-4 text-sm font-semibold text-slate-900">📋 Informasi Sekolah & Jurusan</h3>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Sekolah *</label>
                  <input
                    type="text"
                    name="school_name"
                    value={form.school_name}
                    onChange={handleChange}
                    placeholder="contoh: SMKN 1 Jakarta"
                    className={`w-full rounded-xl border ${errors.school_name ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                    required
                  />
                  {errors.school_name && <p className="mt-1 text-xs text-red-600">{errors.school_name}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Jurusan *</label>
                  <input
                    type="text"
                    name="major_name"
                    value={form.major_name}
                    onChange={handleChange}
                    placeholder="contoh: Teknik Komputer & Jaringan"
                    className={`w-full rounded-xl border ${errors.major_name ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                    required
                  />
                  {errors.major_name && <p className="mt-1 text-xs text-red-600">{errors.major_name}</p>}
                </div>
              </div>
            </div>

            {/* Personal Info */}
            <div className="rounded-xl bg-slate-50/80 p-5 border border-slate-200/60">
              <h3 className="mb-4 text-sm font-semibold text-slate-900">👤 Informasi Pribadi</h3>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Nama Siswa *</label>
                  <input type="text" name="name" value={form.name} onChange={handleChange} placeholder="Nama lengkap sesuai identitas"
                    className={`w-full rounded-xl border ${errors.name ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} required />
                  {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">NIS</label>
                  <input type="text" name="nis" value={form.nis} onChange={handleChange} placeholder="Nomor Induk Siswa"
                    className={`w-full rounded-xl border ${errors.nis ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
                  {errors.nis && <p className="mt-1 text-xs text-red-600">{errors.nis}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Kelas</label>
                  <input type="text" name="class" value={form.class} onChange={handleChange} placeholder="contoh: XII RPL 1"
                    className={`w-full rounded-xl border ${errors.class ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
                  {errors.class && <p className="mt-1 text-xs text-red-600">{errors.class}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Tahun Masuk</label>
                  <input type="number" name="entry_year" value={form.entry_year} onChange={handleChange} placeholder="2024"
                    className={`w-full rounded-xl border ${errors.entry_year ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
                  {errors.entry_year && <p className="mt-1 text-xs text-red-600">{errors.entry_year}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Jenis Kelamin</label>
                  <select
                    name="gender"
                    value={form.gender}
                    onChange={handleChange}
                    className={`w-full rounded-xl border ${errors.gender ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                  >
                    <option value="">Pilih...</option>
                    <option value="male">Laki-laki</option>
                    <option value="female">Perempuan</option>
                  </select>
                  {errors.gender && <p className="mt-1 text-xs text-red-600">{errors.gender}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Tanggal Lahir</label>
                  <input type="date" name="birth_date" value={form.birth_date} onChange={handleChange}
                    className={`w-full rounded-xl border ${errors.birth_date ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
                  {errors.birth_date && <p className="mt-1 text-xs text-red-600">{errors.birth_date}</p>}
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Telepon</label>
                  <input type="text" name="phone" value={form.phone} onChange={handleChange} placeholder="08xxx"
                    className={`w-full rounded-xl border ${errors.phone ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
                  {errors.phone && <p className="mt-1 text-xs text-red-600">{errors.phone}</p>}
                </div>
              </div>
              <div className="mt-4">
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Alamat Rumah</label>
                <input type="text" name="address" value={form.address} onChange={handleChange} placeholder="Alamat rumah lengkap"
                  className={`w-full rounded-xl border ${errors.address ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
                {errors.address && <p className="mt-1 text-xs text-red-600">{errors.address}</p>}
              </div>
            </div>

            {/* Bio */}
            <div className="rounded-xl bg-slate-50/80 p-5 border border-slate-200/60">
              <h3 className="mb-4 text-sm font-semibold text-slate-900">📝 Deskripsi & Minat</h3>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Deskripsi / Minat / Bio</label>
              <textarea
                name="interests"
                value={form.interests}
                onChange={handleChange}
                rows={3}
                placeholder="contoh: Suka web development, data science, dll."
                className={`w-full rounded-xl border ${errors.interests ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none`}
              />
              {errors.interests && <p className="mt-1 text-xs text-red-600">{errors.interests}</p>}
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-3 pt-2">
              <button
                type="button"
                onClick={() => { setEditing(false); setMsg(''); setErrors({}); }}
                className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
              >
                {isNewStudent ? 'Nanti Saja' : 'Batal'}
              </button>
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
      )}
    </div>
  );
}
