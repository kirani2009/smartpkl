import { forwardRef } from 'react';

const Input = forwardRef(function Input(
  { label, error, hint, className = '', icon, ...props },
  ref,
) {
  return (
    <div className="space-y-1.5">
      {label && (
        <label className="block text-sm font-medium text-slate-700">
          {label}
        </label>
      )}
      <div className="relative">
        {icon && (
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            {icon}
          </div>
        )}
        <input
          ref={ref}
          className={`block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all duration-200 input-focus ${
            error
              ? 'border-red-300 text-red-900 placeholder-red-400 focus:border-red-500 focus:ring-red-500/10'
              : 'border-slate-300 hover:border-slate-400'
          } ${icon ? 'pl-10' : ''} ${className}`}
          {...props}
        />
      </div>
      {error && <p className="text-xs text-red-600">{error}</p>}
      {hint && !error && <p className="text-xs text-slate-400">{hint}</p>}
    </div>
  );
});

export default Input;
