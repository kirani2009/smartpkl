const statusConfig = {
  PENDING: { color: 'bg-amber-50 text-amber-700 ring-amber-600/10', dot: 'bg-amber-500', label: 'Pending' },
  ACTIVE: { color: 'bg-emerald-50 text-emerald-700 ring-emerald-600/10', dot: 'bg-emerald-500', label: 'Aktif' },
  ACCEPTED: { color: 'bg-emerald-50 text-emerald-700 ring-emerald-600/10', dot: 'bg-emerald-500', label: 'Diterima' },
  REVIEWED: { color: 'bg-blue-50 text-blue-700 ring-blue-600/10', dot: 'bg-blue-500', label: 'Review' },
  REJECTED: { color: 'bg-red-50 text-red-700 ring-red-600/10', dot: 'bg-red-500', label: 'Ditolak' },
  CANCELLED: { color: 'bg-slate-50 text-slate-600 ring-slate-500/10', dot: 'bg-slate-400', label: 'Dibatalkan' },
  COMPLETED: { color: 'bg-emerald-50 text-emerald-700 ring-emerald-600/10', dot: 'bg-emerald-500', label: 'Selesai' },
  INTERVIEW: { color: 'bg-violet-50 text-violet-700 ring-violet-600/10', dot: 'bg-violet-500', label: 'Interview' },
  PUBLISHED: { color: 'bg-emerald-50 text-emerald-700 ring-emerald-600/10', dot: 'bg-emerald-500', label: 'Published' },
  DRAFT: { color: 'bg-slate-50 text-slate-600 ring-slate-500/10', dot: 'bg-slate-400', label: 'Draft' },
  CLOSED: { color: 'bg-slate-50 text-slate-600 ring-slate-500/10', dot: 'bg-slate-400', label: 'Ditutup' },
  ONGOING: { color: 'bg-blue-50 text-blue-700 ring-blue-600/10', dot: 'bg-blue-500', label: 'Berlangsung' },
  PREPARATION: { color: 'bg-amber-50 text-amber-700 ring-amber-600/10', dot: 'bg-amber-500', label: 'Persiapan' },
  SCHEDULED: { color: 'bg-blue-50 text-blue-700 ring-blue-600/10', dot: 'bg-blue-500', label: 'Terjadwal' },
};

const defaultConfig = { color: 'bg-slate-50 text-slate-600 ring-slate-500/10', dot: 'bg-slate-400', label: '-' };

export default function StatusBadge({ status, className = '' }) {
  const key = String(status).toUpperCase();
  const config = statusConfig[key] || defaultConfig;

  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ${config.color} ${className}`}
    >
      <span className={`h-1.5 w-1.5 rounded-full ${config.dot}`} />
      {status}
    </span>
  );
}
