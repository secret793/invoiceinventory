import React from 'react';

export default function Spinner({ size = 'md', className = '' }) {
  const s = { sm: 'w-5 h-5 border-2', md: 'w-8 h-8 border-4', lg: 'w-12 h-12 border-4' };
  return (
    <div className={`${s[size] || s.md} border-blue-600 border-t-transparent rounded-full animate-spin ${className}`} />
  );
}
