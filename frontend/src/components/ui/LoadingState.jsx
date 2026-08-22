export default function LoadingState({ text = 'Memuat data...' }) {
  return (
    <div className="flex flex-col items-center justify-center py-16">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600" />
      <p className="mt-4 text-sm text-slate-500">{text}</p>
    </div>
  );
}
