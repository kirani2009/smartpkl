import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useFetch, useMutation } from '../../hooks/useApi';
import { LoadingState, ErrorState } from '../../components/ui';

export default function CompanyProfileSetup() {
  const navigate = useNavigate();
  const { data: profileData, loading: profileLoading, error: profileError, refetch } = useFetch('/me/company');
  const { mutate } = useMutation();

  const [form, setForm] = useState({
    name: '', industry: '', address: '', city: '', phone: '',
    email: '', website: '', description: '', established_year: '', employee_count: '',
  });
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');
  const [errors, setErrors] = useState({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profileData && !initialized) {
      const profile = profileData.profile;
      setForm({
        name: profile?.name || '',
        industry: profile?.industry || '',
        address: profile?.address || '',
        city: profile?.city || '',
        phone: profile?.phone || '',
        email: profile?.email || '',
        website: profile?.website || '',
        description: profile?.description || '',
        established_year: profile?.established_year || '',
        employee_count: profile?.employee_count || '',
      });
      setInitialized(true);
    }
  }, [profileData, initialized]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setErrors({ ...errors, [e.target.name]: '' });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMsg('');
    setErrors({});

    try {
      const payload = { ...form };
      Object.keys(payload).forEach((key) => {
        if (payload[key] === '' || payload[key] === null) payload[key] = null;
      });

      const method = profileData?.profile ? 'put' : 'post';
      const res = await mutate(method, '/me/company', payload);
      if (res?.success) {
        setMsg('Profil perusahaan berhasil disimpan!');
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

  if (profileLoading) return <LoadingState />;
  if (profileError && !initialized) return <ErrorState message={profileError} onRetry={refetch} />;

  const hasProfile = !!profileData?.profile;

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* Welcome Banner */}
      {!hasProfile && (
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-700 p-8 text-white shadow-xl">
          <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
          <div className="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-white/5 blur-2xl" />
          <div className="relative z-10 text-center">
            <span className="text-4xl">🏢</span>
            <h1 className="mt-3 text-2xl font-bold">Selamat Datang di SmartPKL!</h1>
            <p className="mt-2 text-sm text-white/80">
              Silakan lengkapi profil perusahaan Anda untuk mulai menerima lamaran dari siswa PKL.
            </p>
          </div>
        </div>
      )}

      {hasProfile && (
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Profil Perusahaan</h1>
          <p className="text-sm text-slate-500">Kelola informasi profil perusahaan Anda.</p>
        </div>
      )}

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

      {/* Profile Form */}
      <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-8 shadow-sm backdrop-blur-xl">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Company Identity */}
          <div className="rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-5 border border-blue-200/60">
            <h3 className="mb-4 text-sm font-semibold text-slate-900">🏢 Identitas Perusahaan</h3>
            <div className="space-y-4">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Nama Perusahaan *</label>
                <input
                  type="text" name="name" value={form.name} onChange={handleChange} required
                  placeholder="PT TechCorp Indonesia"
                  className={`w-full rounded-xl border ${errors.name ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                />
                {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Industri</label>
                  <input
                    type="text" name="industry" value={form.industry} onChange={handleChange}
                    placeholder="Technology, Manufacturing..."
                    className={`w-full rounded-xl border ${errors.industry ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Email Perusahaan</label>
                  <input
                    type="email" name="email" value={form.email} onChange={handleChange}
                    placeholder="hrd@perusahaan.com"
                    className={`w-full rounded-xl border ${errors.email ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                  />
                </div>
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Website</label>
                <input
                  type="text" name="website" value={form.website} onChange={handleChange}
                  placeholder="https://perusahaan.com"
                  className={`w-full rounded-xl border ${errors.website ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                />
              </div>
            </div>
          </div>

          {/* Location & Contact */}
          <div className="rounded-xl bg-slate-50/80 p-5 border border-slate-200/60">
            <h3 className="mb-4 text-sm font-semibold text-slate-900">📍 Lokasi & Kontak</h3>
            <div className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Kota</label>
                  <input
                    type="text" name="city" value={form.city} onChange={handleChange}
                    placeholder="Jakarta Selatan"
                    className={`w-full rounded-xl border ${errors.city ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-xs font-medium text-slate-600">Telepon</label>
                  <input
                    type="text" name="phone" value={form.phone} onChange={handleChange}
                    placeholder="021-1234567"
                    className={`w-full rounded-xl border ${errors.phone ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                  />
                </div>
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Alamat</label>
                <input
                  type="text" name="address" value={form.address} onChange={handleChange}
                  placeholder="Jl. Sudirman Kav. 52-53"
                  className={`w-full rounded-xl border ${errors.address ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                />
              </div>
            </div>
          </div>

          {/* Company Details */}
          <div className="rounded-xl bg-slate-50/80 p-5 border border-slate-200/60">
            <h3 className="mb-4 text-sm font-semibold text-slate-900">📊 Detail Perusahaan</h3>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Tahun Berdiri</label>
                <input
                  type="number" name="established_year" value={form.established_year} onChange={handleChange}
                  placeholder="2015"
                  className={`w-full rounded-xl border ${errors.established_year ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-slate-600">Jumlah Karyawan</label>
                <input
                  type="number" name="employee_count" value={form.employee_count} onChange={handleChange}
                  placeholder="50"
                  className={`w-full rounded-xl border ${errors.employee_count ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`}
                />
              </div>
            </div>
          </div>

          {/* Description */}
          <div className="rounded-xl bg-slate-50/80 p-5 border border-slate-200/60">
            <h3 className="mb-4 text-sm font-semibold text-slate-900">📝 Deskripsi Perusahaan</h3>
            <textarea
              name="description" value={form.description} onChange={handleChange} rows={4}
              placeholder="Ceritakan tentang perusahaan Anda, visi, misi, dan budaya kerja..."
              className={`w-full rounded-xl border ${errors.description ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none`}
            />
            {errors.description && <p className="mt-1 text-xs text-red-600">{errors.description}</p>}
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => navigate('/company/dashboard')}
              className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50"
            >
              {hasProfile ? 'Kembali' : 'Nanti Saja'}
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
    </div>
  );
}
