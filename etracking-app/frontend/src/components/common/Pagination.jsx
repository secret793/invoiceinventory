import React from 'react';

export default function Pagination({
  meta = {},
  onPageChange,
  onPerPageChange,
  pageSizeOptions = [25, 50, 100],
  allowAll = false,
}) {
  const { current_page = 1, last_page = 1, total = 0, per_page = 25, from = 0, to = 0 } = meta;
  const hasPager = last_page > 1;
  const hasPageSizeControl = typeof onPerPageChange === 'function';

  if (!hasPager && !hasPageSizeControl) return null;

  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, current_page - 2);
  let end   = Math.min(last_page, start + maxVisible - 1);
  if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

  for (let p = start; p <= end; p++) pages.push(p);

  const btn = 'px-3 py-1.5 text-sm rounded-lg transition-colors';
  const perPageValue = allowAll && total > 0 && per_page >= total ? 'all' : String(per_page);
  const perPageChoices = [...pageSizeOptions];
  if (allowAll && !perPageChoices.includes('all')) perPageChoices.push('all');

  return (
    <div className="flex items-center justify-between flex-wrap gap-3 pt-4">
      <div className="flex items-center gap-3 flex-wrap">
        <p className="text-sm text-gray-500">
          Showing <strong>{from}</strong>–<strong>{to}</strong> of <strong>{total}</strong> records
        </p>
        {hasPageSizeControl && (
          <label className="flex items-center gap-2 text-sm text-gray-500">
            <span>Rows per page</span>
            <select
              className="input input-sm w-28"
              value={perPageValue}
              onChange={(e) => {
                const next = e.target.value === 'all' ? (total || per_page || 100) : parseInt(e.target.value, 10);
                onPerPageChange(next);
              }}
            >
              {perPageChoices.map((option) => (
                <option key={option} value={option}>
                  {option === 'all' ? 'All' : option}
                </option>
              ))}
            </select>
          </label>
        )}
      </div>
      {hasPager && (
        <div className="flex items-center gap-1">
          <button disabled={current_page <= 1}
            onClick={() => onPageChange(current_page - 1)}
            className={`${btn} ${current_page <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-100 text-gray-600'}`}>
            ‹
          </button>
          {start > 1 && (
            <>
              <button onClick={() => onPageChange(1)} className={`${btn} hover:bg-gray-100 text-gray-600`}>1</button>
              {start > 2 && <span className="px-1 text-gray-400">…</span>}
            </>
          )}
          {pages.map(p => (
            <button key={p} onClick={() => onPageChange(p)}
              className={`${btn} ${p === current_page ? 'bg-blue-600 text-white font-medium' : 'hover:bg-gray-100 text-gray-600'}`}>
              {p}
            </button>
          ))}
          {end < last_page && (
            <>
              {end < last_page - 1 && <span className="px-1 text-gray-400">…</span>}
              <button onClick={() => onPageChange(last_page)} className={`${btn} hover:bg-gray-100 text-gray-600`}>{last_page}</button>
            </>
          )}
          <button disabled={current_page >= last_page}
            onClick={() => onPageChange(current_page + 1)}
            className={`${btn} ${current_page >= last_page ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-100 text-gray-600'}`}>
            ›
          </button>
        </div>
      )}
    </div>
  );
}
