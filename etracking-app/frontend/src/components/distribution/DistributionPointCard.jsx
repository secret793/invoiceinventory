import React from 'react';
import { Link } from 'react-router-dom';

export default function DistributionPointCard({ dp, onEdit, onDelete }) {
  return (
    <div className="card hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between mb-4">
        <div className="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-lg">
          🌐
        </div>
        <div className="flex items-center gap-1">
          {onEdit && (
            <button onClick={() => onEdit(dp)}
              className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">✏️</button>
          )}
          {onDelete && (
            <button onClick={() => onDelete(dp)}
              className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">🗑️</button>
          )}
        </div>
      </div>

      <h3 className="font-semibold text-gray-900 mb-1">{dp.name}</h3>
      <p className="text-sm text-gray-500 mb-4">{dp.location || dp.region || '—'}</p>

      <div className="flex items-center gap-4 text-sm">
        <div className="text-center">
          <p className="font-bold text-green-600 text-lg">{dp.received_count ?? 0}</p>
          <p className="text-gray-400 text-xs">Received</p>
        </div>
        <div className="text-center">
          <p className="font-bold text-blue-600 text-lg">{dp.other_count ?? 0}</p>
          <p className="text-gray-400 text-xs">Active</p>
        </div>
      </div>

      <div className="mt-4 pt-4 border-t border-gray-100">
        <Link to={`/distribution/${dp.id}`} className="btn-secondary btn-sm w-full text-center block">
          View Devices
        </Link>
      </div>
    </div>
  );
}
