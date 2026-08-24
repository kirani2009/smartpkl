const statusColors = {
  PENDING: 'yellow',
  ACTIVE: 'green',
  ACCEPTED: 'green',
  REVIEWED: 'blue',
  REJECTED: 'red',
  CANCELLED: 'red',
  COMPLETED: 'green',
  INTERVIEW: 'blue',
  PUBLISHED: 'green',
  DRAFT: 'gray',
  CLOSED: 'gray',
  ONGOING: 'green',
  PREPARATION: 'yellow',
};

export default function StatusBadge({ status, className = '' }) {
  const key = String(status).toUpperCase();
  const color = statusColors[key] || 'gray';
  const colorClass = {
    green: 'bg-brand-100 text-brand-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
    blue: 'bg-blue-100 text-blue-700',
    gray: 'bg-slate-100 text-slate-600',
  }[color];

  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass} ${className}`}
    >
      {status}
    </span>
  );
}
