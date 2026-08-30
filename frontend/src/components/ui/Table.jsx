export default function Table({ columns = [], data = [], emptyMessage = 'Tidak ada data' }) {
  if (!data || data.length === 0) {
    return null;
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-200/80">
      <table className="min-w-full divide-y divide-slate-200/80">
        <thead className="bg-slate-50/80">
          <tr>
            {columns.map((col) => (
              <th
                key={col.key}
                className="whitespace-nowrap px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
              >
                {col.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100 bg-white">
          {data.map((row, idx) => (
            <tr key={row.id || idx} className="transition-colors hover:bg-slate-50/60">
              {columns.map((col) => (
                <td key={col.key} className="whitespace-nowrap px-5 py-3.5 text-sm text-slate-700">
                  {col.render ? col.render(row[col.key], row) : row[col.key] || '-'}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
