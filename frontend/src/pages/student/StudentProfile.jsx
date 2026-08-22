import { useState, useEffect } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Input, Select, Button, LoadingState, ErrorState } from '../../components/ui';

export default function StudentProfile() {
  const { data: profile, loading, error, refetch } = useFetch('/me/student');
  const { data: schools } = useFetch('/schools');
  const { mutate } = useMutation();

  const [editing, setEditing] = useState(false);
  const [form, setForm] = useState({});
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');
  const [errors, setErrors] = useState({});

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
    setErrors({ ...errors, [e.target.name]: '' });
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMsg('');
    setErrors({});
    try {
      const method = profile ? 'put' : 'post';
      const res = await mutate(method, '/me/student', form);
      if (res?.success) {
        setMsg('Profil berhasil disimpan.');
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
  if (error) return <ErrorState message={error} />;

  const completeness = profile?.profile_completeness ?? 0;
  const missingFields = profile?.missing_fields ?? [];
  const schoolOptions = schools?.map((s) => ({ value: s.id, label: s.name })) || [];

  const completenessColor = completeness >= 80 ? 'green' : completeness >= 50 ? 'yellow' : 'red';

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Profil Siswa</h1>
          <p className="text-sm text-slate-500">Lengkapi profil Anda untuk meningkatkan peluang diterima.</p>
        </div>
        {!editing && (
          <Button onClick={() => setEditing(true)}>Edit Profil</Button>
        )}
      </div>

      {/* Completeness Indicator */}
      {completeness < 100 && (
        <Card>
          <Card.Body>
            <div className="flex items-center gap-4">
              <div className="flex-1">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium text-slate-700">Kelengkapan Profil</span>
                  <span className={`text-sm font-bold ${
                    completenessColor === 'green' ? 'text-brand-600' :
                    completenessColor === 'yellow' ? 'text-yellow-600' : 'text-red-600'
                  }`}>{completeness}%</span>
                </div>
                <div className="h-2 w-full rounded-full bg-slate-100">
                  <div
                    className={`h-2 rounded-full transition-all ${
                      completenessColor === 'green' ? 'bg-brand-500' :
                      completenessColor === 'yellow' ? 'bg-yellow-500' : 'bg-red-500'
                    }`}
                    style={{ width: `${completeness}%` }}
                  />
                </div>
              </div>
            </div>
            {missingFields.length > 0 && (
              <div className="mt-3">
                <p className="text-xs font-medium text-slate-500">Data yang masih perlu dilengkapi:</p>
                <div className="mt-1 flex flex-wrap gap-1">
                  {missingFields.map((field) => (
                    <span key={field} className="rounded-full bg-yellow-50 px-2 py-0.5 text-xs text-yellow-700">
                      {field}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </Card.Body>
        </Card>
      )}

      {/* View Mode */}
      {!editing && profile && (
        <Card>
          <Card.Body className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-xs font-medium text-slate-500">Nama</p>
                <p className="text-sm text-slate-900">{profile.user?.name || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Email</p>
                <p className="text-sm text-slate-900">{profile.user?.email || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">NIS/NISN</p>
                <p className="text-sm text-slate-900">{profile.nis || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Kelas</p>
                <p className="text-sm text-slate-900">{profile.class || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Sekolah</p>
                <p className="text-sm text-slate-900">{profile.school?.name || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Jurusan</p>
                <p className="text-sm text-slate-900">{profile.major?.name || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Jenis Kelamin</p>
                <p className="text-sm text-slate-900">{profile.gender === 'male' ? 'Laki-laki' : profile.gender === 'female' ? 'Perempuan' : '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Tahun Masuk</p>
                <p className="text-sm text-slate-900">{profile.entry_year || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Telepon</p>
                <p className="text-sm text-slate-900">{profile.phone || '-'}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-slate-500">Tanggal Lahir</p>
                <p className="text-sm text-slate-900">{profile.birth_date || '-'}</p>
              </div>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500">Alamat</p>
              <p className="text-sm text-slate-900">{profile.address || '-'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500">Minat/Bio</p>
              <p className="text-sm text-slate-900">{profile.interests || '-'}</p>
            </div>
            {profile.skills?.length > 0 && (
              <div>
                <p className="text-xs font-medium text-slate-500">Skill</p>
                <div className="mt-1 flex flex-wrap gap-1">
                  {profile.skills.map((skill) => (
                    <span key={skill.id} className="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">
                      {skill.name} {skill.level && `(${skill.level})`}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </Card.Body>
        </Card>
      )}

      {/* Edit Mode */}
      {editing && (
        <Card>
          <Card.Body>
            {msg && (
              <div className={`mb-4 rounded-lg p-3 text-sm ${msg.includes('berhasil') ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-700'}`}>
                {msg}
              </div>
            )}

            <form onSubmit={handleSave} className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <Input label="NIS" name="nis" value={form.nis} onChange={handleChange} placeholder="Nomor Induk Siswa" error={errors.nis} />
                <Input label="Kelas" name="class" value={form.class} onChange={handleChange} placeholder="contoh: XII RPL 1" error={errors.class} />
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <Input label="Tahun Masuk" name="entry_year" type="number" value={form.entry_year} onChange={handleChange} placeholder="2024" error={errors.entry_year} />
                <Select
                  label="Jenis Kelamin"
                  name="gender"
                  value={form.gender}
                  onChange={handleChange}
                  options={[{ value: 'male', label: 'Laki-laki' }, { value: 'female', label: 'Perempuan' }]}
                  placeholder="Pilih..."
                  error={errors.gender}
                />
              </div>
              <Input label="Tanggal Lahir" name="birth_date" type="date" value={form.birth_date} onChange={handleChange} error={errors.birth_date} />
              <Input label="Telepon" name="phone" value={form.phone} onChange={handleChange} placeholder="08xxx" error={errors.phone} />
              <Select label="Sekolah" name="school_id" value={form.school_id} onChange={handleChange} options={schoolOptions} placeholder="Pilih sekolah..." error={errors.school_id} />
              <Input label="Minat/Bio" name="interests" value={form.interests} onChange={handleChange} placeholder="contoh: Web Development, Data Science" error={errors.interests} />
              <Input label="Alamat" name="address" value={form.address} onChange={handleChange} placeholder="Alamat lengkap" error={errors.address} />

              <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => { setEditing(false); setMsg(''); setErrors({}); }}>
                  Batal
                </Button>
                <Button type="submit" disabled={saving}>
                  {saving ? 'Menyimpan...' : 'Simpan Profil'}
                </Button>
              </div>
            </form>
          </Card.Body>
        </Card>
      )}
    </div>
  );
}
