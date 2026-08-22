/**
 * StatusBadge — automatically colors based on common status strings.
 */

const statusColors = {
  PENDING: 'yellow',
  ACTIVE: 'green',
  ACCEPTED: 'green',
  REJECTED: 'red',
  CANCELLED: 'red',
  COMPLETED: 'blue',
  INTERVIEW: 'purple',
  REVIEW: 'blue',
  PUBLISHED: 'green',
  DRAFT: 'gray',
  CLOSED: 'gray',
  ONGOING: 'blue',
  PREPARATION: 'yellow',
};

export default function StatusBadge({ status, className = '' }) {
  const key = String(status).toUpperCase();
  const color = statusColors[key] || 'gray';
  const colorClass = {
    blue: 'bg-blue-100 text-blue-700',
    green: 'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
    gray: 'bg-slate-100 text-slate-600',
    purple: 'bg-purple-100 text-purple-700',
  }[color];

  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass} ${className}`}
    >
      {status}
    </span>
  );
}
