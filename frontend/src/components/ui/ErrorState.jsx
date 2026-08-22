import Button from './Button';

export default function ErrorState({ message = 'Terjadi kesalahan.', onRetry }) {
  return (
    <div className="flex flex-col items-center justify-center py-16">
      <span className="text-4xl">⚠️</span>
      <h3 className="mt-4 text-sm font-semibold text-slate-700">{message}</h3>
      {onRetry && (
        <Button variant="secondary" size="sm" className="mt-4" onClick={onRetry}>
          Coba Lagi
        </Button>
      )}
    </div>
  );
}
