import { useState, useEffect } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Pagination, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

export default function CompanyInternships() {
  const { data, loading, error, refetch } = useFetch('/company/internships');
  const { data: profileData } = useFetch('/me/company');
  const { mutate } = useMutation();

  const companyName = profileData?.profile?.name || '';

  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({
    title: '', position: '', description: '', location: '', quota: '',
    period_start: '', period_end: '', required_major: '', required_skills: '',
  });
  const [requirements, setRequirements] = useState(['']);
  const [formErrors, setFormErrors] = useState({});
  const [submitError, setSubmitError] = useState('');
  const [saving, setSaving] = useState(false);

  // Auto-fill title when company name loads
  useEffect(() => {
    if (companyName && form.title === '') {
      setForm((prev) => ({ ...prev, title: companyName }));
    }
  }, [companyName]);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const internships = data?.items || data || [];
  const meta = data?.meta;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm({ ...form, [name]: value });
    if (formErrors[name]) setFormErrors({ ...formErrors, [name]: '' });
    setSubmitError('');
  };

  const handleRequirementChange = (index, value) => {
    const updated = [...requirements];
    updated[index] = value;
    setRequirements(updated);
  };

  const addRequirement = () => { if (requirements.length < 20) setRequirements([...requirements, '']); };
  const removeRequirement = (index) => { setRequirements(requirements.filter((_, i) => i !== index)); };

  const resetForm = () => {
    setForm({ title: companyName, position: '', description: '', location: '', quota: '', period_start: '', period_end: '', required_major: '', required_skills: '' });
    setRequirements(['']);
    setFormErrors({});
    setSubmitError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setFormErrors({});
    setSubmitError('');

    const payload = { ...form };
    ['location', 'required_major', 'required_skills'].forEach((key) => {
      if (payload[key] === '' || payload[key] === null) payload[key] = null;
    });
    if (payload.quota) {
      payload.quota = parseInt(payload.quota, 10);
      if (isNaN(payload.quota)) { setFormErrors({ quota: 'Kuota harus berupa angka.' }); setSaving(false); return; }
    }
    const reqs = requirements.filter((r) => r.trim() !== '');
    if (reqs.length > 0) payload.requirements = reqs;

    try {
      const res = await mutate('post', '/company/internships', payload);
      if (res?.success) { setShowModal(false); resetForm(); refetch(); }
      else setSubmitError(res?.message || 'Gagal menyimpan lowongan.');
    } catch (err) {
      const data = err?.response?.data;
      if (err?.response?.status === 401) { setSubmitError('Sesi berakhir. Login kembali.'); return; }
      if (data?.errors) {
        const fe = {};
        for (const [key, msgs] of Object.entries(data.errors)) fe[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        setFormErrors(fe);
        setSubmitError('Mohon periksa kembali data yang diisi.');
      } else setSubmitError(data?.message || 'Gagal menyimpan lowongan.');
    } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      const res = await mutate('delete', `/company/internships/${deleteTarget.id}`);
      if (res?.success) { setDeleteTarget(null); refetch(); }
    } catch { /* handled */ }
    finally { setDeleting(false); }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Lowongan PKL</h1>
          <p className="text-sm text-slate-500">Kelola lowongan praktik kerja perusahaan Anda.</p>
        </div>
        <button
          onClick={() => { resetForm(); setShowModal(true); }}
          className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300"
        >
          + Buat Lowongan
        </button>
      </div>

      {/* Error */}
      {error && (
        <div className="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          <span>⚠️</span> {error}
          <button onClick={refetch} className="ml-auto text-sm font-semibold underline">Coba lagi</button>
        </div>
      )}

      {/* Internship List */}
      {loading ? (
        <LoadingState text="Memuat lowongan..." />
      ) : !internships.length ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl">
          <EmptyState title="Belum ada lowongan" description="Buat lowongan pertama Anda untuk mulai menerima pelamar." icon="📋" />
        </div>
      ) : (
        <div className="space-y-4">
          {internships.map((internship) => (
            <div key={internship.id} className="group overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 p-6 shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-blue-300 hover:shadow-lg hover:shadow-slate-200/50">
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3">
                    <h3 className="text-base font-bold text-slate-900 group-hover:text-blue-700">{internship.title}</h3>
                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                      internship.status === 'PUBLISHED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                      internship.status === 'CLOSED' ? 'bg-red-50 text-red-700 border border-red-200' :
                      'bg-slate-100 text-slate-600 border border-slate-200'
                    }`}>
                      {internship.status === 'PUBLISHED' ? '🟢 Published' : internship.status}
                    </span>
                  </div>

                  {internship.position && <p className="mt-1 text-sm text-slate-600">💼 {internship.position}</p>}

                  <div className="mt-3 flex flex-wrap gap-1.5">
                    {(internship.required_major || internship.major?.name) && (
                      <span className="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                        🎓 {internship.required_major || internship.major?.name}
                      </span>
                    )}
                    {internship.location && (
                      <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                        📍 {internship.location}
                      </span>
                    )}
                    {internship.quota && (
                      <span className="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                        👥 Kuota: {internship.quota}
                      </span>
                    )}
                    {internship.applications_count > 0 && (
                      <span className="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 border border-purple-200">
                        📄 {internship.applications_count} lamaran
                      </span>
                    )}
                  </div>

                  {internship.description && <p className="mt-2 text-xs text-slate-500 line-clamp-2">{internship.description}</p>}
                  {internship.required_skills && <p className="mt-1.5 text-xs text-slate-400"><span className="font-medium">Skill:</span> {internship.required_skills}</p>}
                  {internship.period_start && internship.period_end && (
                    <p className="mt-1 text-xs text-slate-400">📅 {new Date(internship.period_start).toLocaleDateString('id-ID')} — {new Date(internship.period_end).toLocaleDateString('id-ID')}</p>
                  )}
                </div>

                <button
                  onClick={() => setDeleteTarget(internship)}
                  className="flex-shrink-0 rounded-lg p-2 text-red-400 transition-all hover:bg-red-50 hover:text-red-600"
                  title="Hapus lowongan"
                >
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          ))}
          {meta && meta.last_page > 1 && <Pagination meta={meta} onPageChange={() => {}} />}
        </div>
      )}

      {/* Create Modal */}
      <Modal open={showModal} onClose={() => setShowModal(false)} title="Buat Lowongan Baru" maxWidth="max-w-xl">
        <form onSubmit={handleSubmit} className="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
          {submitError && (
            <div className="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
              <span>⚠️</span> {submitError}
            </div>
          )}

          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Informasi Dasar</h4>
          </div>

          {/* Title - Auto-filled with company name */}
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Judul Lowongan *</label>
            <input type="text" name="title" value={form.title} onChange={handleChange} required readOnly
              className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 cursor-not-allowed" />
            <p className="mt-1 text-xs text-slate-400">Judul otomatis menggunakan nama perusahaan.</p>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Posisi / Bidang</label>
              <input type="text" name="position" value={form.position} onChange={handleChange}
                placeholder="contoh: Web Developer"
                className={`w-full rounded-xl border ${formErrors.position ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Jurusan yang Dibutuhkan</label>
              <input type="text" name="required_major" value={form.required_major} onChange={handleChange}
                placeholder="contoh: Teknik Informatika"
                className={`w-full rounded-xl border ${formErrors.required_major ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Lokasi</label>
              <input type="text" name="location" value={form.location} onChange={handleChange}
                placeholder="contoh: Jakarta Selatan"
                className={`w-full rounded-xl border ${formErrors.location ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Kuota *</label>
              <input type="number" name="quota" min="1" max="100" value={form.quota} onChange={handleChange} required
                placeholder="3"
                className={`w-full rounded-xl border ${formErrors.quota ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
              {formErrors.quota && <p className="mt-1 text-xs text-red-600">{formErrors.quota}</p>}
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Tanggal Mulai *</label>
              <input type="date" name="period_start" value={form.period_start} onChange={handleChange} required
                className={`w-full rounded-xl border ${formErrors.period_start ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-slate-600">Tanggal Selesai *</label>
              <input type="date" name="period_end" value={form.period_end} onChange={handleChange} required
                className={`w-full rounded-xl border ${formErrors.period_end ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20`} />
            </div>
          </div>

          {/* Description */}
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Deskripsi *</label>
            <textarea rows={4} name="description" value={form.description} onChange={handleChange} required
              placeholder="Jelaskan tentang lowongan PKL, tugas yang akan dikerjakan, dll."
              className={`w-full rounded-xl border ${formErrors.description ? 'border-red-400 bg-red-50/50' : 'border-slate-300 bg-white'} px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none`} />
            {formErrors.description && <p className="mt-1 text-xs text-red-600">{formErrors.description}</p>}
          </div>

          {/* Skills */}
          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Skill yang Dibutuhkan (Opsional)</h4>
            <textarea rows={2} name="required_skills" value={form.required_skills} onChange={handleChange}
              placeholder="contoh: JavaScript, React, Node.js, PHP, MySQL"
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none" />
          </div>

          {/* Requirements */}
          <div>
            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Persyaratan (Opsional)</h4>
            <div className="space-y-2">
              {requirements.map((req, idx) => (
                <div key={idx} className="flex gap-2">
                  <input
                    className="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    placeholder={`Persyaratan ${idx + 1}`}
                    value={req}
                    onChange={(e) => handleRequirementChange(idx, e.target.value)}
                  />
                  {requirements.length > 1 && (
                    <button type="button" onClick={() => removeRequirement(idx)} className="rounded-lg px-2 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">✕</button>
                  )}
                </div>
              ))}
              {requirements.length < 20 && (
                <button type="button" onClick={addRequirement} className="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">+ Tambah Persyaratan</button>
              )}
            </div>
          </div>

          <div className="flex justify-end gap-2 border-t border-slate-200 pt-4">
            <button type="button" onClick={() => setShowModal(false)}
              className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50">Batal</button>
            <button type="submit" disabled={saving}
              className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300 disabled:opacity-50">
              {saving ? 'Menyimpan...' : '📤 Publikasikan'}
            </button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal open={!!deleteTarget} onClose={() => setDeleteTarget(null)} title="Hapus Lowongan" maxWidth="max-w-md">
        <div className="space-y-4">
          <div className="flex items-center gap-3 rounded-xl bg-red-50 p-4 border border-red-200">
            <span className="text-2xl">🗑️</span>
            <div>
              <p className="text-sm font-medium text-red-800">Hapus lowongan ini?</p>
              <p className="text-xs text-red-600">Lowongan <strong>{deleteTarget?.title}</strong> akan dihapus permanen.</p>
            </div>
          </div>
          <p className="text-xs text-slate-500">Lowongan yang sudah memiliki lamaran tidak dapat dihapus.</p>
          <div className="flex justify-end gap-2">
            <button onClick={() => setDeleteTarget(null)}
              className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all hover:bg-slate-50">Batal</button>
            <button onClick={handleDelete} disabled={deleting}
              className="rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-red-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-300 disabled:opacity-50">
              {deleting ? 'Menghapus...' : 'Ya, Hapus'}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
