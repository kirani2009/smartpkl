import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, Input, Select, Modal, StatusBadge, Table, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function CompanyInternships() {
  const { data, loading, error, refetch } = useFetch('/company/internships');
  const { mutate } = useMutation();

  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ title: '', position: '', description: '', location: '', quota: '', start_date: '', end_date: '' });
  const [saving, setSaving] = useState(false);

  const internships = data?.items || data || [];

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      await mutate('post', '/company/internships', form);
      setShowModal(false);
      setForm({ title: '', position: '', description: '', location: '', quota: '', start_date: '', end_date: '' });
      refetch();
    } catch {
      // handled
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
        <Button onClick={() => setShowModal(true)}>+ Buat Lowongan</Button>
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
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input label="Judul Lowongan" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
          <Input label="Posisi" value={form.position} onChange={(e) => setForm({ ...form, position: e.target.value })} required />
          <Input label="Lokasi" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} />
          <Input label="Kuota" type="number" value={form.quota} onChange={(e) => setForm({ ...form, quota: e.target.value })} />
          <div className="grid grid-cols-2 gap-4">
            <Input label="Tanggal Mulai" type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} />
            <Input label="Tanggal Selesai" type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700">Deskripsi</label>
            <textarea
              className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              rows={4}
              value={form.description}
              onChange={(e) => setForm({ ...form, description: e.target.value })}
            />
          </div>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>Batal</Button>
            <Button type="submit" disabled={saving}>{saving ? 'Menyimpan...' : 'Simpan'}</Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
