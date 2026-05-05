import React from 'react';
import { Link } from 'react-router-dom';

export default function AllocationPointCard({ ap, onEdit, onDelete }) {
  return (
    <div className="card hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between mb-4">
        <div className="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-lg flex-shrink-0">
          📍
        </div>
        <div className="flex items-center gap-1">
          {onEdit && (
            <button onClick={() => onEdit(ap)}
              className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
              ✏️
            </button>
          )}
          {onDelete && (
            <button onClick={() => onDelete(ap)}
              className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
              🗑️
            </button>
          )}
        </div>
      </div>

      <h3 className="font-semibold text-gray-900 mb-1">{ap.name}</h3>
      <p className="text-sm text-gray-500 mb-4">{ap.location || ap.region || '—'}</p>

      <div className="flex items-center gap-4 text-sm">
        <div className="text-center">
          <p className="font-bold text-blue-600 text-lg">{ap.received_count ?? 0}</p>
          <p className="text-gray-400 text-xs">Received</p>
        </div>
        <div className="text-center">
          <p className="font-bold text-green-600 text-lg">{ap.other_count ?? 0}</p>
          <p className="text-gray-400 text-xs">Active</p>
        </div>
      </div>

      <div className="mt-4 pt-4 border-t border-gray-100 flex gap-2">
        <Link to={`/allocation/${ap.id}`} className="btn-secondary btn-sm flex-1 text-center">
          View Devices
        </Link>
        <Link to={`/data-entry/${ap.id}`} className="btn-primary btn-sm flex-1 text-center">
          Data Entry
        </Link>
      </div>
    </div>
  );
}
