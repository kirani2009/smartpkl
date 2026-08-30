export default function LoadingState({ text = 'Memuat data...', compact = false }) {
  if (compact) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="flex items-center gap-3">
          <div className="h-5 w-5 animate-spin rounded-full border-2 border-brand-200 border-t-brand-600" />
          <p className="text-sm text-slate-500">{text}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center justify-center py-16">
      <div className="relative">
        <div className="h-10 w-10 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600" />
      </div>
      <p className="mt-4 text-sm font-medium text-slate-500">{text}</p>
    </div>
  );
}
