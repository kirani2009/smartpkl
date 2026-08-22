import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Input, Button, LoadingState } from '../../components/ui';

export default function CompanyProfileSetup() {
  const navigate = useNavigate();
  const { data: profile, loading: profileLoading } = useFetch('/me/company');
  const { mutate } = useMutation();

  const [form, setForm] = useState({
    name: '',
    industry: '',
    address: '',
    city: '',
    phone: '',
    email: '',
    website: '',
    description: '',
    established_year: '',
    employee_count: '',
  });
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');
  const [errors, setErrors] = useState({});

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
      // Convert empty strings to null for optional fields
      Object.keys(payload).forEach((key) => {
        if (payload[key] === '') payload[key] = null;
      });

      const res = await mutate('post', '/me/company', payload);
      if (res?.success) {
        navigate('/company/dashboard');
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

  // If profile already exists, redirect to dashboard
  if (profile?.profile) {
    navigate('/company/dashboard');
    return null;
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* Welcome Banner */}
      <Card>
        <Card.Body className="text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-brand-100">
            <span className="text-3xl">🏢</span>
          </div>
          <h1 className="text-2xl font-bold text-slate-900">Selamat Datang di SmartPKL!</h1>
          <p className="mt-2 text-sm text-slate-600">
            Silakan lengkapi profil perusahaan Anda untuk mulai menerima lamaran dari siswa PKL.
          </p>
        </Card.Body>
      </Card>

      {/* Profile Form */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Profil Perusahaan</h2>
        </Card.Header>
        <Card.Body>
          {msg && (
            <div className={`mb-4 rounded-lg p-3 text-sm ${msg.includes('berhasil') ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-700'}`}>
              {msg}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Nama Perusahaan *"
              name="name"
              value={form.name}
              onChange={handleChange}
              placeholder="PT TechCorp Indonesia"
              error={errors.name}
              required
            />
            <Input
              label="Industri"
              name="industry"
              value={form.industry}
              onChange={handleChange}
              placeholder="Technology, Manufacturing, Finance..."
              error={errors.industry}
            />
            <div className="grid gap-4 sm:grid-cols-2">
              <Input
                label="Kota"
                name="city"
                value={form.city}
                onChange={handleChange}
                placeholder="Jakarta Selatan"
                error={errors.city}
              />
              <Input
                label="Telepon"
                name="phone"
                value={form.phone}
                onChange={handleChange}
                placeholder="021-1234567"
                error={errors.phone}
              />
            </div>
            <Input
              label="Alamat"
              name="address"
              value={form.address}
              onChange={handleChange}
              placeholder="Jl. Sudirman Kav. 52-53"
              error={errors.address}
            />
            <div className="grid gap-4 sm:grid-cols-2">
              <Input
                label="Email Perusahaan"
                type="email"
                name="email"
                value={form.email}
                onChange={handleChange}
                placeholder="hrd@perusahaan.com"
                error={errors.email}
              />
              <Input
                label="Website"
                name="website"
                value={form.website}
                onChange={handleChange}
                placeholder="https://perusahaan.com"
                error={errors.website}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Input
                label="Tahun Berdiri"
                type="number"
                name="established_year"
                value={form.established_year}
                onChange={handleChange}
                placeholder="2015"
                error={errors.established_year}
              />
              <Input
                label="Jumlah Karyawan"
                type="number"
                name="employee_count"
                value={form.employee_count}
                onChange={handleChange}
                placeholder="50"
                error={errors.employee_count}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700">Deskripsi Perusahaan</label>
              <textarea
                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                rows={4}
                name="description"
                value={form.description}
                onChange={handleChange}
                placeholder="Ceritakan tentang perusahaan Anda, visi, misi, dan budaya kerja..."
              />
              {errors.description && <p className="mt-1 text-xs text-red-600">{errors.description}</p>}
            </div>

            <div className="flex justify-end gap-2 pt-2">
              <Button type="button" variant="secondary" onClick={() => navigate('/company/dashboard')}>
                Nanti Saja
              </Button>
              <Button type="submit" disabled={saving}>
                {saving ? 'Menyimpan...' : 'Simpan Profil'}
              </Button>
            </div>
          </form>
        </Card.Body>
      </Card>
    </div>
  );
}
