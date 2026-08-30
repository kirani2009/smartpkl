export default function EmptyState({ icon = '📭', title = 'Tidak ada data', description = '', action }) {
  return (
    <div className="flex flex-col items-center justify-center py-12">
      <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
        <span className="text-3xl">{icon}</span>
      </div>
      <h3 className="mt-4 text-sm font-semibold text-slate-700">{title}</h3>
      {description && (
        <p className="mt-1 max-w-sm text-center text-sm text-slate-500">{description}</p>
      )}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}
