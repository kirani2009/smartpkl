import { useFetch } from '../../hooks/useApi';
import { Card, StatusBadge, LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function StudentInterviews() {
  const { data, loading, error, refetch } = useFetch('/student/interviews');

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Interview</h1>
        <p className="text-sm text-slate-500">Jadwal interview dari perusahaan.</p>
      </div>

      {loading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !data?.length ? (
        <EmptyState title="Belum ada interview" description="Anda akan mendapat notifikasi jika perusahaan menjadwalkan interview." />
      ) : (
        <div className="space-y-3">
          {data.map((interview) => (
            <Card key={interview.id}>
              <Card.Body>
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-sm font-semibold text-slate-900">{interview.title || 'Interview'}</p>
                    <p className="mt-0.5 text-xs text-slate-500">
                      {interview.application?.internship?.company?.name}
                    </p>
                    {interview.scheduled_at && (
                      <p className="mt-1 text-xs text-slate-500">
                        📅 {new Date(interview.scheduled_at).toLocaleString('id-ID')}
                      </p>
                    )}
                    {interview.location && (
                      <p className="mt-0.5 text-xs text-slate-500">📍 {interview.location}</p>
                    )}
                    {interview.notes && (
                      <p className="mt-1 text-xs text-slate-400">{interview.notes}</p>
                    )}
                  </div>
                  <StatusBadge status={interview.status} />
                </div>
              </Card.Body>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
