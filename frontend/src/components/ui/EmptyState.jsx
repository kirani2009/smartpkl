export default function EmptyState({ icon = '📭', title = 'Tidak ada data', description = '' }) {
  return (
    <div className="flex flex-col items-center justify-center py-16">
      <span className="text-4xl">{icon}</span>
      <h3 className="mt-4 text-sm font-semibold text-slate-700">{title}</h3>
      {description && (
        <p className="mt-1 max-w-sm text-center text-sm text-slate-500">{description}</p>
      )}
    </div>
  );
}
