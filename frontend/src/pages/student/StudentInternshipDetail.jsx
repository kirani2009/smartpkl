import { useState, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useFetch, useMutation } from '../../hooks/useApi';
import { LoadingState, ErrorState } from '../../components/ui';
import api from '../../api/api';

export default function StudentInternshipDetail() {
  const { id } = useParams();
  const { data: internship, loading, error, refetch } = useFetch(`/student/internships/${id}`);
  const { mutate } = useMutation();

  const [message, setMessage] = useState('');
  const [applying, setApplying] = useState(false);
  const [result, setResult] = useState(null);

  const [files, setFiles] = useState({ cv: null, ijazah: null, portfolio: null });
  const [fileNames, setFileNames] = useState({ cv: '', ijazah: '', portfolio: '' });

  const cvRef = useRef(null);
  const ijazahRef = useRef(null);
  const portfolioRef = useRef(null);

  const handleFileChange = (type, e) => {
    const file = e.target.files[0];
    if (file) {
      setFiles((prev) => ({ ...prev, [type]: file }));
      setFileNames((prev) => ({ ...prev, [type]: file.name }));
    }
  };

  const handleRemoveFile = (type) => {
    setFiles((prev) => ({ ...prev, [type]: null }));
    setFileNames((prev) => ({ ...prev, [type]: '' }));
    if (type === 'cv' && cvRef.current) cvRef.current.value = '';
    if (type === 'ijazah' && ijazahRef.current) ijazahRef.current.value = '';
    if (type === 'portfolio' && portfolioRef.current) portfolioRef.current.value = '';
  };

  const handleApply = async () => {
    setApplying(true);
    setResult(null);
    try {
      const formData = new FormData();
      if (message) formData.append('message', message);
      if (files.cv) formData.append('cv', files.cv);
      if (files.ijazah) formData.append('ijazah', files.ijazah);
      if (files.portfolio) formData.append('portfolio', files.portfolio);

      const { data: res } = await api.post(`/student/internships/${id}/apply`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      setResult(res.success ? { type: 'success', text: res.message } : { type: 'error', text: res.message });
    } catch (err) {
      setResult({ type: 'error', text: err.response?.data?.message || 'Gagal melamar.' });
    } finally {
      setApplying(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;
  if (!internship) return <ErrorState message="Lowongan tidak ditemukan." />;

  const fileFields = [
    { key: 'cv', label: 'CV', ref: cvRef, accept: '.pdf,.doc,.docx', icon: '📄', formats: 'PDF, DOC' },
    { key: 'ijazah', label: 'Ijazah', ref: ijazahRef, accept: '.pdf,.jpg,.jpeg,.png', icon: '🎓', formats: 'PDF, JPG' },
    { key: 'portfolio', label: 'Portofolio', ref: portfolioRef, accept: '.pdf,.doc,.docx,.ppt,.pptx,.zip', icon: '💼', formats: 'PDF, DOC, PPT, ZIP' },
  ];

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      {/* Back Link */}
      <Link to="/internships" className="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 transition-colors hover:text-blue-800">
        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar lowongan
      </Link>

      {/* Job Detail Card */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
        {/* Header */}
        <div className="border-b border-slate-100 px-8 py-6">
          <div className="flex items-start justify-between">
            <div className="flex items-start gap-4">
              <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-lg font-bold text-white shadow-lg shadow-blue-200">
                {(internship.company?.name || 'C').charAt(0)}
              </div>
              <div>
                <h1 className="text-xl font-bold text-slate-900">{internship.title}</h1>
                <p className="text-sm text-slate-500">{internship.company?.name}</p>
              </div>
            </div>
            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${
              internship.status === 'PUBLISHED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
              internship.status === 'CLOSED' ? 'bg-red-50 text-red-700 border border-red-200' :
              'bg-slate-100 text-slate-600 border border-slate-200'
            }`}>
              {internship.status === 'PUBLISHED' ? '🟢 Published' : internship.status}
            </span>
          </div>
        </div>

        {/* Info Grid */}
        <div className="px-8 py-6">
          <div className="grid gap-4 sm:grid-cols-2">
            {[
              { icon: '📍', label: 'Lokasi', value: internship.location },
              { icon: '👥', label: 'Kuota', value: internship.quota ? `${internship.quota} orang` : null },
              { icon: '🎓', label: 'Jurusan', value: internship.major?.name || internship.required_major || 'Semua Jurusan' },
              { icon: '📅', label: 'Periode', value: internship.period_start ? `${new Date(internship.period_start).toLocaleDateString('id-ID')} — ${new Date(internship.period_end).toLocaleDateString('id-ID')}` : null },
            ].filter(i => i.value).map((item) => (
              <div key={item.label} className="flex items-start gap-3 rounded-xl bg-slate-50/80 p-3.5">
                <span className="text-lg">{item.icon}</span>
                <div>
                  <p className="text-xs font-medium text-slate-500">{item.label}</p>
                  <p className="text-sm font-medium text-slate-900">{item.value}</p>
                </div>
              </div>
            ))}
          </div>

          {/* Description */}
          {internship.description && (
            <div className="mt-5 rounded-xl bg-slate-50/80 p-5">
              <h3 className="mb-2 text-sm font-semibold text-slate-900">📝 Deskripsi Pekerjaan</h3>
              <p className="text-sm leading-relaxed text-slate-700 whitespace-pre-line">{internship.description}</p>
            </div>
          )}

          {/* Requirements */}
          {internship.requirements?.length > 0 && (
            <div className="mt-5 rounded-xl bg-slate-50/80 p-5">
              <h3 className="mb-2 text-sm font-semibold text-slate-900">📋 Persyaratan</h3>
              <ul className="space-y-1.5">
                {internship.requirements.map((req) => (
                  <li key={req.id} className="flex items-start gap-2 text-sm text-slate-700">
                    <span className="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-500" />
                    {req.description}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Skills */}
          {internship.skills?.length > 0 && (
            <div className="mt-5">
              <h3 className="mb-2 text-sm font-semibold text-slate-900">🛠️ Skill yang Dibutuhkan</h3>
              <div className="flex flex-wrap gap-1.5">
                {internship.skills.map((skill) => (
                  <span key={skill.id} className="rounded-full bg-gradient-to-r from-blue-50 to-indigo-50 px-3 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                    {skill.name}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Apply Section */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl">
        <div className="border-b border-slate-100 px-8 py-4">
          <h2 className="text-sm font-semibold text-slate-900">📨 Kirim Lamaran</h2>
        </div>
        <div className="p-8 space-y-5">
          {/* Result */}
          {result && (
            <div className={`flex items-center gap-3 rounded-xl border p-4 text-sm font-medium ${
              result.type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-red-200 bg-red-50 text-red-700'
            }`}>
              <span>{result.type === 'success' ? '✅' : '⚠️'}</span>
              <span>{result.text}</span>
              {result.type === 'success' && (
                <Link to="/student/applications" className="ml-auto text-sm font-semibold underline">Lihat Lamaran →</Link>
              )}
            </div>
          )}

          {/* Message */}
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Deskripsi / Keterangan Lamaran</label>
            <textarea
              rows={4}
              placeholder="Tulis deskripsi atau keterangan untuk lamaran ini (opsional)..."
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none"
            />
          </div>

          {/* File Uploads */}
          <div>
            <label className="mb-1.5 block text-xs font-medium text-slate-600">Lampiran File</label>
            <p className="mb-3 text-xs text-slate-400">Upload CV, Ijazah, atau Portofolio (opsional)</p>
            <div className="space-y-3">
              {fileFields.map(({ key, label, ref, accept, icon, formats }) => (
                <div key={key} className="flex items-center gap-3">
                  <div className="flex-1">
                    <input ref={ref} type="file" accept={accept} onChange={(e) => handleFileChange(key, e)} className="hidden" id={`file-${key}`} />
                    <label
                      htmlFor={`file-${key}`}
                      className={`flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed px-4 py-3.5 text-sm transition-all duration-200 ${
                        files[key]
                          ? 'border-blue-300 bg-blue-50'
                          : 'border-slate-300 bg-white hover:border-blue-300 hover:bg-blue-50/50'
                      }`}
                    >
                      <span className="text-xl">{files[key] ? '✅' : icon}</span>
                      <div className="flex-1 min-w-0">
                        {files[key] ? (
                          <span className="truncate block text-sm font-medium text-slate-900">{fileNames[key]}</span>
                        ) : (
                          <span className="text-sm text-slate-500">Pilih file {label}...</span>
                        )}
                      </div>
                      <span className="flex-shrink-0 text-xs text-slate-400">{formats}</span>
                    </label>
                  </div>
                  {files[key] && (
                    <button type="button" onClick={() => handleRemoveFile(key)} className="flex-shrink-0 rounded-lg p-2 text-red-500 transition-all hover:bg-red-50">
                      ✕
                    </button>
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Submit */}
          <div className="flex justify-end pt-2">
            <button
              onClick={handleApply}
              disabled={applying}
              className="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-blue-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {applying ? (
                <span className="flex items-center gap-2">
                  <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                  Mengirim...
                </span>
              ) : '🎓 Kirim Lamaran'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
