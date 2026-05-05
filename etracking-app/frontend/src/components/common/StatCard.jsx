import React from 'react';

export default function StatCard({ label, value, icon, color = 'blue', change, onClick }) {
  const colors = {
    blue:   'bg-blue-50 text-blue-600',
    green:  'bg-green-50 text-green-600',
    yellow: 'bg-yellow-50 text-yellow-600',
    red:    'bg-red-50 text-red-600',
    purple: 'bg-purple-50 text-purple-600',
    gray:   'bg-gray-100 text-gray-600',
    orange: 'bg-orange-50 text-orange-600',
  };

  return (
    <div onClick={onClick}
      className={`card flex items-center gap-4 ${onClick ? 'cursor-pointer hover:shadow-md transition-shadow' : ''}`}>
      {icon && (
        <div className={`flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-xl ${colors[color] || colors.blue}`}>
          {icon}
        </div>
      )}
      <div className="min-w-0">
        <p className="text-sm text-gray-500 font-medium truncate">{label}</p>
        <p className="text-2xl font-bold text-gray-900 leading-tight">{value ?? '—'}</p>
        {change !== undefined && (
          <p className={`text-xs mt-0.5 ${change >= 0 ? 'text-green-600' : 'text-red-600'}`}>
            {change >= 0 ? '▲' : '▼'} {Math.abs(change)}%
          </p>
        )}
      </div>
    </div>
  );
}
