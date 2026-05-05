import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function AuthLayout() {
  const { user, loading } = useAuth();
  if (loading) return <div className="min-h-screen flex items-center justify-center"><Spinner /></div>;
  if (user)    return <Navigate to="/dashboard" replace />;
  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-2xl mb-4">
            <span className="text-3xl">📡</span>
          </div>
          <h1 className="text-3xl font-bold text-white">GNSW E-Tracking</h1>
          <p className="text-blue-200 mt-1 text-sm">Inventory & Device Management System</p>
        </div>
        <div className="bg-white rounded-2xl shadow-2xl p-8">
          <Outlet />
        </div>
      </div>
    </div>
  );
}

function Spinner() {
  return <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />;
}
