import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useSidebar } from '../contexts/SidebarContext';
import { useNotification } from '../contexts/NotificationContext';

export default function TopBar() {
  const { user, logout }       = useAuth();
  const { toggleCollapse }     = useSidebar();
  const { unreadCount }        = useNotification();
  const navigate               = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const initials = user?.name
    ? user.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
    : 'U';

  return (
    <header className="h-14 flex items-center justify-between px-4 flex-shrink-0 z-10"
      style={{ background: '#1E2D7A', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>

      {/* Left: hamburger + date */}
      <div className="flex items-center gap-3">
        <button
          onClick={toggleCollapse}
          className="p-2 rounded-lg transition-colors"
          style={{ color: 'rgba(255,255,255,0.7)' }}
          onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.1)'}
          onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <span className="text-sm hidden sm:block" style={{ color: 'rgba(255,255,255,0.55)' }}>
          {new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}
        </span>
      </div>

      {/* Right: notifications + user */}
      <div className="flex items-center gap-1">
        {/* Notification bell */}
        <Link
          to="/notifications"
          className="relative p-2 rounded-lg transition-colors"
          style={{ color: 'rgba(255,255,255,0.75)' }}
          onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.1)'}
          onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          {unreadCount > 0 && (
            <span className="absolute -top-0.5 -right-0.5 text-white text-[10px] font-bold
              rounded-full w-4 h-4 flex items-center justify-center"
              style={{ background: '#E31E24' }}>
              {unreadCount > 9 ? '9+' : unreadCount}
            </span>
          )}
        </Link>

        {/* Divider */}
        <div className="w-px h-6 mx-1" style={{ background: 'rgba(255,255,255,0.15)' }} />

        {/* User menu */}
        <div className="relative group">
          <button
            className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg transition-colors"
            onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.1)'}
            onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
          >
            <div className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
              style={{ background: '#E31E24', color: 'white' }}>
              {initials}
            </div>
            <div className="hidden sm:block text-left">
              <p className="text-sm font-semibold leading-none" style={{ color: 'white' }}>{user?.name}</p>
              <p className="text-xs mt-0.5" style={{ color: 'rgba(255,255,255,0.5)' }}>{user?.roles?.[0] || 'User'}</p>
            </div>
            <svg className="w-3.5 h-3.5 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"
              style={{ color: 'rgba(255,255,255,0.5)' }}>
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          {/* Dropdown */}
          <div className="absolute right-0 top-full mt-2 w-52 bg-white rounded-xl shadow-2xl border border-gray-100
            opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50"
            style={{ boxShadow: '0 12px 40px rgba(30,45,122,0.2)' }}>
            <div className="p-3 rounded-t-xl" style={{ background: '#1E2D7A' }}>
              <p className="text-sm font-semibold text-white">{user?.name}</p>
              <p className="text-xs mt-0.5" style={{ color: 'rgba(255,255,255,0.6)' }}>{user?.email}</p>
            </div>
            <div className="py-1">
              <button
                onClick={handleLogout}
                className="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm rounded-b-xl transition-colors"
                style={{ color: '#E31E24' }}
                onMouseEnter={e => e.currentTarget.style.background = '#FEF2F2'}
                onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sign Out
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
