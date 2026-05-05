import React from 'react';

export default function DataTable({
  columns = [],
  data    = [],
  loading = false,
  emptyMessage = 'No records found.',
  selectable = false,
  selected   = [],
  onSelect   = null,
  onSelectAll = null,
  className = '',
  rowKey = 'id',
}) {
  const allSelected = data.length > 0 && data.every(r => selected.includes(r[rowKey]));

  if (loading) {
    return (
      <div className="flex items-center justify-center py-16">
        <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className={`table-wrap ${className}`}>
      <table className="table">
        <thead>
          <tr>
            {selectable && (
              <th className="w-10">
                <input type="checkbox"
                  checked={allSelected}
                  onChange={e => onSelectAll?.(e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
              </th>
            )}
            {columns.map((col, i) => (
              <th key={i} className={col.className || ''} style={col.width ? { width: col.width } : {}}>
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-100">
          {data.length === 0 ? (
            <tr>
              <td colSpan={columns.length + (selectable ? 1 : 0)}
                className="text-center py-12 text-gray-400 italic">
                {emptyMessage}
              </td>
            </tr>
          ) : data.map((row, ri) => (
            <tr key={row[rowKey] ?? ri}>
              {selectable && (
                <td>
                  <input type="checkbox"
                    checked={selected.includes(row[rowKey])}
                    onChange={e => onSelect?.(row[rowKey], e.target.checked)}
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                </td>
              )}
              {columns.map((col, ci) => (
                <td key={ci} className={col.cellClassName || ''}>
                  {col.render ? col.render(row[col.key], row) : (row[col.key] ?? '—')}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
