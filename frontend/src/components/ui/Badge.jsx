const colorMap = {
  green: 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
  yellow: 'bg-amber-50 text-amber-700 ring-amber-600/10',
  red: 'bg-red-50 text-red-700 ring-red-600/10',
  blue: 'bg-blue-50 text-blue-700 ring-blue-600/10',
  gray: 'bg-slate-50 text-slate-600 ring-slate-500/10',
  brand: 'bg-brand-50 text-brand-700 ring-brand-600/10',
};

export default function Badge({ color = 'green', className = '', children }) {
  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ${colorMap[color] || colorMap.gray} ${className}`}
    >
      {children}
    </span>
  );
}
