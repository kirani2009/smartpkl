import { useState, useEffect } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, Input, Modal, StatusBadge, Table, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function CompanyInternships() {
  const { data, loading, error, refetch } = useFetch('/company/internships');
  const { data: schoolsData } = useFetch('/schools');
  const { data: skillsData } = useFetch('/skills');
  const { mutate } = useMutation();

  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({
    title: '',
    position: '',
    description: '',
    location: '',
    quota: '',
    period_start: '',
    period_end: '',
    school_id: '',
    major_id: '',
    allowance: '',
    facilities: '',
  });
  const [requirements, setRequirements] = useState(['']);
  const [selectedSkillIds, setSelectedSkillIds] = useState([]);
  const [formErrors, setFormErrors] = useState({});
  const [submitError, setSubmitError] = useState('');
  const [saving, setSaving] = useState(false);

  const internships = data?.items || data || [];
  const schools = schoolsData?.items || schoolsData || [];
  const skills = skillsData?.items || skillsData || [];

  // Fetch majors when school changes
  const [majors, setMajors] = useState([]);
  useEffect(() => {
    if (form.school_id) {
      import('../../api/api').then(({ default: api }) => {
        api.get(`/schools/${form.school_id}/majors`).then((res) => {
          if (res.data.success) {
            setMajors(res.data.data?.items || res.data.data || []);
          }
        }).catch(() => setMajors([]));
      });
    } else {
      setMajors([]);
      setForm((f) => ({ ...f, major_id: '' }));
    }
  }, [form.school_id]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm({ ...form, [name]: value });
    if (formErrors[name]) {
      setFormErrors({ ...formErrors, [name]: '' });
    }
    setSubmitError('');
  };

  const handleRequirementChange = (index, value) => {
    const updated = [...requirements];
    updated[index] = value;
    setRequirements(updated);
  };

  const addRequirement = () => {
    if (requirements.length < 20) {
      setRequirements([...requirements, '']);
    }
  };

  const removeRequirement = (index) => {
    setRequirements(requirements.filter((_, i) => i !== index));
  };

  const toggleSkill = (skillId) => {
    setSelectedSkillIds((prev) =>
      prev.includes(skillId) ? prev.filter((id) => id !== skillId) : [...prev, skillId]
    );
  };

  const resetForm = () => {
    setForm({
      title: '', position: '', description: '', location: '',
      quota: '', period_start: '', period_end: '',
      school_id: '', major_id: '', allowance: '', facilities: '',
    });
    setRequirements(['']);
    setSelectedSkillIds([]);
    setFormErrors({});
    setSubmitError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setFormErrors({});
    setSubmitError('');

    // Build payload
    const payload = { ...form };
    // Convert empty strings to null for optional fields
    ['school_id', 'major_id', 'allowance', 'location', 'facilities'].forEach((key) => {
      if (payload[key] === '' || payload[key] === null) payload[key] = null;
    });
    // Parse quota to integer
    if (payload.quota) payload.quota = parseInt(payload.quota, 10);
    // Parse allowance to number
    if (payload.allowance) payload.allowance = parseFloat(payload.allowance);
    // Add requirements (filter empty)
    const reqs = requirements.filter((r) => r.trim() !== '');
    if (reqs.length > 0) payload.requirements = reqs;
    // Add skill_ids
    if (selectedSkillIds.length > 0) payload.skill_ids = selectedSkillIds;

    try {
      await mutate('post', '/company/internships', payload);
      setShowModal(false);
      resetForm();
      refetch();
    } catch (err) {
      const validationErrors = err?.response?.data?.errors;
      if (validationErrors) {
        const fe = {};
        for (const [key, msgs] of Object.entries(validationErrors)) {
          fe[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        }
        setFormErrors(fe);
        setSubmitError('Mohon periksa kembali data yang diisi.');
      } else {
        setSubmitError(err?.response?.data?.message || 'Gagal menyimpan lowongan.');
      }
    } finally {
      setSaving(false);
    }
  };

  const columns = [
    { key: 'title', label: 'Judul' },
    { key: 'position', label: 'Posisi' },
    { key: 'location', label: 'Lokasi' },
    { key: 'quota', label: 'Kuota' },
    { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Lowongan PKL</h1>
          <p className="text-sm text-slate-500">Kelola lowongan praktik kerja perusahaan Anda.</p>
        </div>
        <Button onClick={() => { resetForm(); setShowModal(true); }}>+ Buat Lowongan</Button>
      </div>

      <Card>
        {loading ? (
          <LoadingState />
        ) : error ? (
          <ErrorState message={error} onRetry={refetch} />
        ) : !internships.length ? (
          <EmptyState title="Belum ada lowongan" description="Buat lowongan pertama Anda." />
        ) : (
          <Table columns={columns} data={internships} emptyMessage="Tidak ada lowongan." />
        )}
      </Card>

      {/* Create Modal */}
      <Modal open={showModal} onClose={() => setShowModal(false)} title="Buat Lowongan Baru" maxWidth="max-w-xl">
        <form onSubmit={handleSubmit} className="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
          {submitError && (
            <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{submitError}</div>
          )}

          {/* Informasi Dasar */}
          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Dasar</h4>
          </div>
          <Input label="Judul Lowongan *" name="title" value={form.title} onChange={handleChange} error={formErrors.title} required />
          <Input label="Posisi" name="position" value={form.position} onChange={handleChange} error={formErrors.position} />
          <Input label="Lokasi" name="location" value={form.location} onChange={handleChange} error={formErrors.location} />
          <Input label="Kuota *" name="quota" type="number" min="1" max="100" value={form.quota} onChange={handleChange} error={formErrors.quota} required />
          <div className="grid grid-cols-2 gap-4">
            <Input label="Tanggal Mulai *" name="period_start" type="date" value={form.period_start} onChange={handleChange} error={formErrors.period_start} required />
            <Input label="Tanggal Selesai *" name="period_end" type="date" value={form.period_end} onChange={handleChange} error={formErrors.period_end} required />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700">Deskripsi *</label>
            <textarea
              className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 ${
                formErrors.description ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-slate-300'
              }`}
              rows={4} name="description" value={form.description} onChange={handleChange} required
            />
            {formErrors.description && <p className="mt-1 text-xs text-red-600">{formErrors.description}</p>}
          </div>

          {/* Target Sekolah */}
          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400">Target (Opsional)</h4>
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700">Sekolah Target</label>
            <select
              className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              name="school_id" value={form.school_id} onChange={handleChange}
            >
              <option value="">Semua Sekolah (Lowongan Umum)</option>
              {schools.map((s) => (
                <option key={s.id} value={s.id}>{s.name}</option>
              ))}
            </select>
            {formErrors.school_id && <p className="mt-1 text-xs text-red-600">{formErrors.school_id}</p>}
          </div>
          {form.school_id && majors.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-slate-700">Jurusan Target</label>
              <select
                className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                name="major_id" value={form.major_id} onChange={handleChange}
              >
                <option value="">Semua Jurusan</option>
                {majors.map((m) => (
                  <option key={m.id} value={m.id}>{m.name}</option>
                ))}
              </select>
              {formErrors.major_id && <p className="mt-1 text-xs text-red-600">{formErrors.major_id}</p>}
            </div>
          )}

          {/* Persyaratan */}
          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400">Persyaratan (Opsional)</h4>
          </div>
          <div className="space-y-2">
            {requirements.map((req, idx) => (
              <div key={idx} className="flex gap-2">
                <input
                  className="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                  placeholder={`Persyaratan ${idx + 1}`}
                  value={req}
                  onChange={(e) => handleRequirementChange(idx, e.target.value)}
                />
                {requirements.length > 1 && (
                  <button type="button" onClick={() => removeRequirement(idx)} className="rounded-lg px-2 text-slate-400 hover:bg-slate-100 hover:text-red-500">✕</button>
                )}
              </div>
            ))}
            {requirements.length < 20 && (
              <button type="button" onClick={addRequirement} className="text-xs font-medium text-brand-600 hover:text-brand-700">+ Tambah Persyaratan</button>
            )}
          </div>

          {/* Skills */}
          {skills.length > 0 && (
            <>
              <div>
                <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400">Skill yang Dibutuhkan (Opsional)</h4>
              </div>
              <div className="flex flex-wrap gap-2">
                {skills.map((skill) => (
                  <button
                    key={skill.id}
                    type="button"
                    onClick={() => toggleSkill(skill.id)}
                    className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                      selectedSkillIds.includes(skill.id)
                        ? 'bg-brand-600 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    {skill.name}
                  </button>
                ))}
              </div>
            </>
          )}

          {/* Fasilitas & Allowance */}
          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400">Fasilitas (Opsional)</h4>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Input label="Uang Saku (Rp)" name="allowance" type="number" min="0" value={form.allowance} onChange={handleChange} error={formErrors.allowance} placeholder="0" />
            <Input label="Fasilitas" name="facilities" value={form.facilities} onChange={handleChange} error={formErrors.facilities} placeholder="Makan, Transport, dll" />
          </div>

          <div className="flex justify-end gap-2 border-t border-slate-200 pt-4">
            <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>Batal</Button>
            <Button type="submit" disabled={saving}>{saving ? 'Menyimpan...' : 'Simpan'}</Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
