import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, StatusBadge, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

export default function TeacherPartnerships() {
  const { data: partnershipsRes, loading, error, refetch } = useFetch('/teacher/partnerships');
  const { mutate } = useMutation();

  const [confirmAction, setConfirmAction] = useState(null);

  // Partnerships: API returns { items: [...], meta: {...} }
  const partnerships = partnershipsRes?.items || partnershipsRes || [];

  const handleAccept = async (partnershipId) => {
    try {
      await mutate('put', `/teacher/partnerships/${partnershipId}`, { status: 'ACCEPTED' });
      refetch();
    } catch {
      // error handled by useMutation
    } finally {
      setConfirmAction(null);
    }
  };

  const handleReject = async (partnershipId) => {
    try {
      await mutate('put', `/teacher/partnerships/${partnershipId}`, { status: 'REJECTED' });
      refetch();
    } catch {
      // error handled by useMutation
    } finally {
      setConfirmAction(null);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Partnership</h1>
        <p className="text-sm text-slate-500">Kelola pengajuan kerja sama dari perusahaan.</p>
      </div>

      {/* Partnership Requests */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Pengajuan Partnership</h2>
        </Card.Header>
        <Card.Body>
          {loading ? (
            <LoadingState />
          ) : error ? (
            <ErrorState message={error} onRetry={refetch} />
          ) : !partnerships.length ? (
            <EmptyState title="Belum ada pengajuan partnership" description="Perusahaan akan mengajukan kerja sama kepada sekolah Anda." />
          ) : (
            <div className="space-y-3">
              {partnerships.map((p) => (
                <div key={p.id} className="rounded-lg border border-slate-100 p-4">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{p.company?.profile?.name || p.company?.name || 'Perusahaan'}</p>
                      <p className="text-xs text-slate-500">
                        Diajukan: {new Date(p.created_at).toLocaleDateString('id-ID')}
                      </p>
                      {p.notes && (
                        <p className="mt-2 text-xs text-slate-600 italic">"{p.notes}"</p>
                      )}
                    </div>
                    <StatusBadge status={p.status} />
                  </div>

                  {p.status === 'PENDING' && (
                    <div className="mt-3 flex gap-2">
                      <Button
                        size="sm"
                        onClick={() => setConfirmAction({ id: p.id, action: 'accept', label: `Terima partnership dari ${p.company?.profile?.name || 'perusahaan'}?` })}
                      >
                        ✅ Terima
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        onClick={() => setConfirmAction({ id: p.id, action: 'reject', label: `Tolak partnership dari ${p.company?.profile?.name || 'perusahaan'}?` })}
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

      {/* Confirmation Modal */}
      <Modal open={!!confirmAction} onClose={() => setConfirmAction(null)} title="Konfirmasi">
        <div className="space-y-4">
          <p className="text-sm text-slate-700">{confirmAction?.label}</p>
          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={() => setConfirmAction(null)}>Batal</Button>
            <Button
              variant={confirmAction?.action === 'reject' ? 'danger' : 'primary'}
              onClick={() => {
                if (confirmAction?.action === 'accept') handleAccept(confirmAction.id);
                else handleReject(confirmAction.id);
              }}
            >
              {confirmAction?.action === 'accept' ? 'Ya, Terima' : 'Ya, Tolak'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
