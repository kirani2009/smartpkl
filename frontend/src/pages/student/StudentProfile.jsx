import { useState, useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Input, Select, Button, LoadingState } from '../../components/ui';

export default function StudentProfile() {
  const { user } = useAuth();
  const { data: profile, loading, refetch } = useFetch('/me/student');
  const { data: schools } = useFetch('/schools');
  const { mutate } = useMutation();

  const [form, setForm] = useState({});
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');

  useEffect(() => {
    if (profile) {
      setForm({
        nis: profile.nis || '',
        class: profile.class || '',
        entry_year: profile.entry_year || '',
        gender: profile.gender || '',
        birth_date: profile.birth_date || '',
        phone: profile.phone || '',
        address: profile.address || '',
        interests: profile.interests || '',
        school_id: profile.school_id || '',
        major_id: profile.major_id || '',
      });
    }
  }, [profile]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMsg('');
    try {
      const method = profile ? 'put' : 'post';
      await mutate(method, '/me/student', form);
      setMsg('Profil berhasil disimpan.');
      refetch();
    } catch {
      setMsg('Gagal menyimpan profil.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <LoadingState />;

  const schoolOptions = schools?.map((s) => ({ value: s.id, label: s.name })) || [];

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Profil Siswa</h1>
        <p className="text-sm text-slate-500">Lengkapi profil Anda untuk meningkatkan peluang diterima.</p>
      </div>

      <Card>
        <Card.Body>
          {msg && (
            <div className={`mb-4 rounded-lg p-3 text-sm ${msg.includes('berhasil') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
              {msg}
            </div>
          )}

          <form onSubmit={handleSave} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <Input label="NIS" name="nis" value={form.nis} onChange={handleChange} placeholder="Nomor Induk Siswa" />
              <Input label="Kelas" name="class" value={form.class} onChange={handleChange} placeholder="contoh: XII RPL 1" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Input label="Tahun Masuk" name="entry_year" type="number" value={form.entry_year} onChange={handleChange} placeholder="2024" />
              <Select
                label="Jenis Kelamin"
                name="gender"
                value={form.gender}
                onChange={handleChange}
                options={[{ value: 'male', label: 'Laki-laki' }, { value: 'female', label: 'Perempuan' }]}
                placeholder="Pilih..."
              />
            </div>
            <Input label="Tanggal Lahir" name="birth_date" type="date" value={form.birth_date} onChange={handleChange} />
            <Input label="Telepon" name="phone" value={form.phone} onChange={handleChange} placeholder="08xxx" />
            <Select label="Sekolah" name="school_id" value={form.school_id} onChange={handleChange} options={schoolOptions} placeholder="Pilih sekolah..." />
            <Input label="Minat" name="interests" value={form.interests} onChange={handleChange} placeholder="contoh: Web Development, Data Science" />
            <Input label="Alamat" name="address" value={form.address} onChange={handleChange} placeholder="Alamat lengkap" />

            <div className="flex justify-end">
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
