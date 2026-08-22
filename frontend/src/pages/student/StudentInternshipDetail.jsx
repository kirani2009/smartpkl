import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, StatusBadge, LoadingState, ErrorState } from '../../components/ui';

export default function StudentInternshipDetail() {
  const { id } = useParams();
  const { data: internship, loading, error, refetch } = useFetch(`/student/internships/${id}`);
  const { mutate } = useMutation();

  const [message, setMessage] = useState('');
  const [applying, setApplying] = useState(false);
  const [result, setResult] = useState(null);

  const handleApply = async () => {
    setApplying(true);
    setResult(null);
    try {
      const res = await mutate('post', `/student/internships/${id}/apply`, { message });
      setResult({ type: 'success', text: res.message });
    } catch (err) {
      setResult({ type: 'error', text: err.response?.data?.message || 'Gagal melamar.' });
    } finally {
      setApplying(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;
  if (!internship) return <ErrorState message="Lowongan tidak ditemukan." />;

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link to="/internships" className="text-sm text-blue-600 hover:underline">← Kembali ke daftar lowongan</Link>

      <Card>
        <Card.Header>
          <div className="flex items-start justify-between">
            <div>
              <h1 className="text-xl font-bold text-slate-900">{internship.title}</h1>
              <p className="text-sm text-slate-600">{internship.company?.name}</p>
            </div>
            <StatusBadge status={internship.status} />
          </div>
        </Card.Header>
        <Card.Body className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <p className="text-xs font-medium text-slate-500">Lokasi</p>
              <p className="text-sm text-slate-900">{internship.location || '-'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500">Kuota</p>
              <p className="text-sm text-slate-900">{internship.quota || '-'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500">Jurusan</p>
              <p className="text-sm text-slate-900">{internship.major?.name || 'Semua Jurusan'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-slate-500">Periode</p>
              <p className="text-sm text-slate-900">
                {internship.start_date ? new Date(internship.start_date).toLocaleDateString('id-ID') : '-'}
                {' — '}
                {internship.end_date ? new Date(internship.end_date).toLocaleDateString('id-ID') : '-'}
              </p>
            </div>
          </div>

          {internship.description && (
            <div>
              <p className="text-xs font-medium text-slate-500">Deskripsi</p>
              <p className="mt-1 text-sm text-slate-700 whitespace-pre-line">{internship.description}</p>
            </div>
          )}

          {internship.requirements?.length > 0 && (
            <div>
              <p className="text-xs font-medium text-slate-500">Persyaratan</p>
              <ul className="mt-1 space-y-1">
                {internship.requirements.map((req) => (
                  <li key={req.id} className="text-sm text-slate-700">• {req.description}</li>
                ))}
              </ul>
            </div>
          )}

          {internship.skills?.length > 0 && (
            <div>
              <p className="text-xs font-medium text-slate-500">Skill yang dibutuhkan</p>
              <div className="mt-1 flex flex-wrap gap-2">
                {internship.skills.map((skill) => (
                  <span key={skill.id} className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs text-blue-700">
                    {skill.name}
                  </span>
                ))}
              </div>
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Apply Section */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Kirim Lamaran</h2>
        </Card.Header>
        <Card.Body className="space-y-4">
          {result && (
            <div className={`rounded-lg p-3 text-sm ${result.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
              {result.text}
            </div>
          )}
          <textarea
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            rows={4}
            placeholder="Tulis pesan untuk perusahaan (opsional)..."
            value={message}
            onChange={(e) => setMessage(e.target.value)}
          />
          <div className="flex justify-end">
            <Button onClick={handleApply} disabled={applying}>
              {applying ? 'Mengirim...' : '🎓 Kirim Lamaran'}
            </Button>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
}
