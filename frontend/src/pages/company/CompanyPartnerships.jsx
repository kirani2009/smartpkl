import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, StatusBadge, LoadingState, EmptyState, ErrorState, Modal } from '../../components/ui';

export default function CompanyPartnerships() {
  const { data, loading, error, refetch } = useFetch('/company/partnerships');
  const { mutate } = useMutation();
  const [confirmAction, setConfirmAction] = useState(null); // { id, action, label }

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

  const partnerships = data?.items || data || [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Partnership</h1>
        <p className="text-sm text-slate-500">Kelola permintaan kerja sama dari sekolah.</p>
      </div>

      <Card>
        <Card.Body>
          {loading ? (
            <LoadingState />
          ) : error ? (
            <ErrorState message={error} onRetry={refetch} />
          ) : !partnerships.length ? (
            <EmptyState title="Belum ada permintaan partnership" description="Sekolah akan mengajukan kerja sama kepada Anda." />
          ) : (
            <div className="space-y-4">
              {partnerships.map((p) => (
                <div key={p.id} className="rounded-lg border border-slate-200 p-4">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{p.school?.name}</p>
                      <p className="text-xs text-slate-500">
                        Guru: {p.requester?.name || '-'}
                      </p>
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
