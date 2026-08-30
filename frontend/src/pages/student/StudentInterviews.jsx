import { useFetch } from '../../hooks/useApi';
import { LoadingState, EmptyState, ErrorState } from '../../components/ui';

export default function StudentInterviews() {
  const { data: interviewsRes, loading, error, refetch } = useFetch('/student/interviews');
  const interviews = interviewsRes?.items || interviewsRes || [];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Jadwal Interview</h1>
        <p className="text-sm text-slate-500">Jadwal interview dari perusahaan.</p>
      </div>

      {/* Results */}
      {loading ? (
        <LoadingState text="Memuat jadwal interview..." />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : !interviews.length ? (
        <div className="rounded-2xl border border-slate-200/60 bg-white/80 p-12 shadow-sm backdrop-blur-xl">
          <EmptyState
            title="Belum ada jadwal interview"
            description="Anda akan mendapat notifikasi jika perusahaan menjadwalkan interview."
            icon="💬"
          />
        </div>
      ) : (
        <div className="space-y-4">
          {interviews.map((interview) => {
            const company = interview.application?.internship?.company;
            const internship = interview.application?.internship;
            const scheduledDate = interview.scheduled_at ? new Date(interview.scheduled_at) : null;

            return (
              <div key={interview.id} className="group overflow-hidden rounded-2xl border border-slate-200/60 bg-white/80 shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-purple-300 hover:shadow-lg hover:shadow-slate-200/50">
                <div className="p-6">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-4 min-w-0 flex-1">
                      {/* Company Avatar */}
                      <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 text-sm font-bold text-white shadow-md shadow-purple-200 transition-transform duration-300 group-hover:scale-110">
                        {(company?.name || company?.profile?.name || 'C').charAt(0)}
                      </div>
                      <div className="min-w-0 flex-1">
                        <h3 className="text-sm font-bold text-slate-900 group-hover:text-purple-700">
                          {internship?.title || 'Interview'}
                        </h3>
                        <p className="text-xs text-slate-500">
                          {company?.name || company?.profile?.name || 'Perusahaan'}
                        </p>
                      </div>
                    </div>

                    {/* Status */}
                    <span className={`flex-shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${
                      interview.status === 'SCHEDULED' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                      interview.status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                      interview.status === 'CANCELLED' ? 'bg-red-50 text-red-700 border border-red-200' :
                      'bg-slate-100 text-slate-600 border border-slate-200'
                    }`}>
                      {interview.status === 'SCHEDULED' ? '📅 Terjadwal' :
                       interview.status === 'COMPLETED' ? '✅ Selesai' :
                       interview.status === 'CANCELLED' ? '❌ Dibatalkan' : interview.status}
                    </span>
                  </div>

                  {/* Schedule Details */}
                  {scheduledDate && (
                    <div className="mt-4 rounded-xl bg-gradient-to-r from-purple-50 to-violet-50 p-4 border border-purple-200/60">
                      <div className="grid gap-3 sm:grid-cols-2">
                        <div>
                          <p className="text-xs font-medium text-purple-600">📅 Tanggal & Waktu</p>
                          <p className="mt-1 text-sm font-semibold text-slate-900">
                            {scheduledDate.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                          </p>
                          <p className="text-sm text-slate-700">
                            {scheduledDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} WIB
                          </p>
                        </div>
                        <div>
                          <p className="text-xs font-medium text-purple-600">📍 Mode & Lokasi</p>
                          <p className="mt-1 text-sm font-semibold text-slate-900">
                            {interview.mode === 'online' ? '💻 Online' : '🏢 Onsite'}
                          </p>
                          {interview.location && (
                            <p className="text-xs text-slate-600">{interview.location}</p>
                          )}
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Notes */}
                  {interview.notes && (
                    <div className="mt-4 rounded-xl bg-slate-50/80 p-4">
                      <p className="text-xs font-medium text-slate-500 mb-1">📝 Deskripsi Interview</p>
                      <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{interview.notes}</p>
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
