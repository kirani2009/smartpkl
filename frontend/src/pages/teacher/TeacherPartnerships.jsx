import { useState } from 'react';
import { useFetch, useMutation } from '../../hooks/useApi';
import { Card, Button, Input, StatusBadge, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function TeacherPartnerships() {
  const { data: partnerships, loading, error, refetch } = useFetch('/teacher/partnerships');
  const { data: companies, loading: companiesLoading } = useFetch('/teacher/partnerships/companies');
  const { mutate } = useMutation();

  const [searchQuery, setSearchQuery] = useState('');
  const [requesting, setRequesting] = useState(null);

  const filteredCompanies = companies?.filter((c) =>
    c.name?.toLowerCase().includes(searchQuery.toLowerCase())
  ) || [];

  const handleRequest = async (companyId) => {
    setRequesting(companyId);
    try {
      await mutate('post', '/teacher/partnerships', { company_id: companyId });
      refetch();
    } catch {
      // error handled by useMutation
    } finally {
      setRequesting(null);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Partnership</h1>
        <p className="text-sm text-slate-500">Cari perusahaan dan ajukan kerja sama.</p>
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
          ) : !partnerships?.items?.length && !partnerships?.length ? (
            <EmptyState title="Belum ada partnership" description="Ajukan kerja sama dengan perusahaan di bawah." />
          ) : (
            <div className="space-y-3">
              {(partnerships?.items || partnerships || []).map((p) => (
                <div key={p.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{p.company?.name}</p>
                    <p className="text-xs text-slate-500">
                      Diajukan: {new Date(p.created_at).toLocaleDateString('id-ID')}
                    </p>
                  </div>
                  <StatusBadge status={p.status} />
                </div>
              ))}
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Search Companies */}
      <Card>
        <Card.Header>
          <h2 className="font-semibold text-slate-900">Cari Perusahaan</h2>
        </Card.Header>
        <Card.Body className="space-y-4">
          <Input
            placeholder="Cari nama perusahaan..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />

          {companiesLoading ? (
            <LoadingState text="Memuat perusahaan..." />
          ) : !filteredCompanies.length ? (
            <p className="py-4 text-center text-sm text-slate-500">Tidak ada perusahaan ditemukan.</p>
          ) : (
            <div className="space-y-2">
              {filteredCompanies.map((company) => {
                const alreadyRequested = (partnerships?.items || partnerships || []).some(
                  (p) => p.company_id === company.id
                );
                return (
                  <div key={company.id} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                    <div>
                      <p className="text-sm font-medium text-slate-900">{company.name}</p>
                      {company.profile?.industry && (
                        <p className="text-xs text-slate-500">{company.profile.industry}</p>
                      )}
                    </div>
                    {alreadyRequested ? (
                      <span className="text-xs text-slate-400">Sudah diajukan</span>
                    ) : (
                      <Button
                        size="sm"
                        onClick={() => handleRequest(company.id)}
                        disabled={requesting === company.id}
                      >
                        {requesting === company.id ? '...' : 'Ajukan'}
                      </Button>
                    )}
                  </div>
                );
              })}
            </div>
          )}
        </Card.Body>
      </Card>
    </div>
  );
}
