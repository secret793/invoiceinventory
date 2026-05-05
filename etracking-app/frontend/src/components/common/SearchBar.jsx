import React, { useState, useCallback } from 'react';

export default function SearchBar({ onSearch, placeholder = 'Search…', className = '', debounce = 400 }) {
  const [value, setValue] = useState('');
  let timer = null;

  const handleChange = (e) => {
    const v = e.target.value;
    setValue(v);
    clearTimeout(timer);
    timer = setTimeout(() => onSearch(v), debounce);
  };

  return (
    <div className={`relative ${className}`}>
      <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
        fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input
        type="text"
        value={value}
        onChange={handleChange}
        placeholder={placeholder}
        className="input pl-9 pr-4"
      />
    </div>
  );
}
