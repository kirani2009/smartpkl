import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, Input, StatusBadge, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

export default function CompanyPartnerships() {
  const { data: partnershipsRes, loading, error, refetch } = useFetch('/company/partnerships');
  const { data: schoolsRes, loading: schoolsLoading } = useFetch('/schools', { params: { per_page: 50 } });
  const { mutate } = useMutation();

  const [searchQuery, setSearchQuery] = useState('');
  const [showProposeModal, setShowProposeModal] = useState(false);
  const [selectedSchool, setSelectedSchool] = useState(null);
  const [notes, setNotes] = useState('');
  const [proposing, setProposing] = useState(false);
  const [confirmAction, setConfirmAction] = useState(null);

  // Partnerships: API returns { items: [...], meta: {...} }
  const partnerships = partnershipsRes?.items || partnershipsRes || [];
  const schools = schoolsRes?.items || schoolsRes || [];

  const filteredSchools = schools.filter((s) =>
    s.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    s.city?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handlePropose = async () => {
    if (!selectedSchool) return;
    setProposing(true);
    try {
      await mutate('post', '/company/partnerships', {
        school_id: selectedSchool.id,
        notes: notes || null,
      });
      setShowProposeModal(false);
      setSelectedSchool(null);
      setNotes('');
      setSearchQuery('');
      refetch();
    } catch {
      // handled by useMutation
    } finally {
      setProposing(false);
    }
  };

  const handleConfirmAction = async () => {
    if (!confirmAction) return;
    try {
      await mutate('put', `/company/partnerships/${confirmAction.id}/${confirmAction.action}`);
      refetch();
    } catch {
      // handled
    } finally {
      setConfirmAction(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Partnership</h1>
          <p className="text-sm text-slate-500">Ajukan kerja sama dengan sekolah.</p>
        </div>
        <Button onClick={() => setShowProposeModal(true)}>+ Ajukan Partnership</Button>
      </div>

      {/* Partnership List */}
      <Card>
        <Card.Body>
          {loading ? (
            <LoadingState />
          ) : error ? (
            <ErrorState message={error} onRetry={refetch} />
          ) : !partnerships.length ? (
            <EmptyState title="Belum ada partnership" description="Ajukan kerja sama dengan sekolah di bawah." />
          ) : (
            <div className="space-y-4">
              {partnerships.map((p) => (
                <div key={p.id} className="rounded-lg border border-slate-200 p-4">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{p.school?.name || 'Sekolah'}</p>
                      {p.school?.city && (
                        <p className="text-xs text-slate-500">{p.school.city}</p>
                      )}
                      {p.notes && (
                        <p className="mt-2 text-xs text-slate-600 italic">"{p.notes}"</p>
                      )}
                      <p className="mt-1 text-xs text-slate-400">
                        Diajukan: {new Date(p.created_at).toLocaleDateString('id-ID')}
                      </p>
                    </div>
                    <StatusBadge status={p.status} />
                  </div>

                  {p.status === 'PENDING' && (
                    <div className="mt-3 flex gap-2">
                      <Button
                        size="sm"
                        onClick={() => setConfirmAction({ id: p.id, action: 'accept', label: `Terima partnership dari ${p.school?.name}?` })}
                      >
                        ✅ Terima
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        onClick={() => setConfirmAction({ id: p.id, action: 'reject', label: `Tolak partnership dari ${p.school?.name}?` })}
                      >
                        ❌ Tolak
                      </Button>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Propose Partnership Modal */}
      <Modal open={showProposeModal} onClose={() => { setShowProposeModal(false); setSelectedSchool(null); setNotes(''); setSearchQuery(''); }} title="Ajukan Partnership" maxWidth="max-w-lg">
        <div className="space-y-4">
          <Input
            placeholder="Cari nama sekolah..."
            value={searchQuery}
            onChange={(e) => { setSearchQuery(e.target.value); setSelectedSchool(null); }}
          />

          {selectedSchool ? (
            <div className="rounded-lg border border-brand-200 bg-brand-50 p-3">
              <p className="text-sm font-semibold text-slate-900">{selectedSchool.name}</p>
              <p className="text-xs text-slate-500">{selectedSchool.city || '-'}</p>
              <button
                type="button"
                onClick={() => setSelectedSchool(null)}
                className="mt-1 text-xs text-brand-600 hover:underline"
              >
                Ganti sekolah
              </button>
            </div>
          ) : (
            <div className="max-h-48 overflow-y-auto">
              {schoolsLoading ? (
                <LoadingState text="Memuat sekolah..." />
              ) : !filteredSchools.length ? (
                <p className="py-4 text-center text-sm text-slate-500">Tidak ada sekolah ditemukan.</p>
              ) : (
                <div className="space-y-2">
                  {filteredSchools.map((school) => {
                    const alreadyProposed = partnerships.some(
                      (p) => p.school_id === school.id
                    );
                    return (
                      <button
                        key={school.id}
                        onClick={() => setSelectedSchool(school)}
                        className="w-full rounded-lg border border-slate-100 p-3 text-left transition hover:border-brand-300 hover:bg-brand-50"
                      >
                        <div className="flex items-center justify-between">
                          <div>
                            <p className="text-sm font-medium text-slate-900">{school.name}</p>
                            <p className="text-xs text-slate-500">{school.city || '-'}</p>
                          </div>
                          {alreadyProposed && (
                            <span className="text-xs text-slate-400">Sudah diajukan</span>
                          )}
                        </div>
                      </button>
                    );
                  })}
                </div>
              )}
            </div>
          )}

          {selectedSchool && (
            <div>
              <label className="block text-sm font-medium text-slate-700">Catatan (Opsional)</label>
              <textarea
                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                rows={3}
                placeholder="Tulis catatan untuk sekolah..."
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
              />
            </div>
          )}

          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={() => { setShowProposeModal(false); setSelectedSchool(null); setNotes(''); setSearchQuery(''); }}>
              Batal
            </Button>
            <Button onClick={handlePropose} disabled={!selectedSchool || proposing}>
              {proposing ? 'Mengirim...' : 'Kirim Pengajuan'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Confirmation Modal */}
      <Modal open={!!confirmAction} onClose={() => setConfirmAction(null)} title="Konfirmasi">
        <div className="space-y-4">
          <p className="text-sm text-slate-700">{confirmAction?.label}</p>
          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={() => setConfirmAction(null)}>Batal</Button>
            <Button
              variant={confirmAction?.action === 'reject' ? 'danger' : 'primary'}
              onClick={handleConfirmAction}
            >
              {confirmAction?.action === 'accept' ? 'Ya, Terima' : 'Ya, Tolak'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
