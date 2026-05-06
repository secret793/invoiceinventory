import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useSidebar } from '../contexts/SidebarContext';
import { useNotification } from '../contexts/NotificationContext';

const NAV_ITEMS = [
  { label: 'Dashboard',       path: '/dashboard',           icon: '🏠',  roles: [] },
  { label: 'Devices',         path: '/devices',             icon: '📱',  roles: [] },
  { label: 'Inventory',       path: '/stores',              icon: '🏭',  roles: [] },
  { label: 'Other Items',     path: '/other-items',         icon: '📦',  roles: [] },
  { label: 'Transfers',       path: '/transfers',           icon: '🔄',  roles: [] },
  {
    label: 'Distribution',
    icon: '🌐',
    roles: [],
    children: 'distributionPoints',
    basePath: '/distribution',
  },
  {
    label: 'Allocation Points',
    icon: '📍',
    roles: [],
    children: 'allocationPoints',
    basePath: '/allocation',
  },
  {
    label: 'Data Entry',
    icon: '✏️',
    roles: [],
    children: 'allocationPoints',
    basePath: '/data-entry',
    childPathPrefix: '/data-entry',
  },
  { label: 'Confirmed Dispatch', path: '/confirmed-affixed',    icon: '✅',  roles: [] },
  { label: 'Device Retrievals',  path: '/device-retrievals',   icon: '🔙',  roles: [] },
  { label: 'Monitoring',         path: '/monitoring',           icon: '👁️', roles: [] },
  { label: 'Receipts',           path: '/receipts',             icon: '🧾',  roles: [] },
  {
    label: 'Reports',
    icon: '📊',
    roles: ['Super Admin', 'Warehouse Manager', 'Finance Officer', 'Report Viewer'],
    path: '/reports',
  },
  {
    label: 'Notifications',
    path: '/notifications',
    icon: '🔔',
    badge: 'notifications',
    roles: [],
  },
  {
    label: 'Configuration',
    icon: '⚙️',
    roles: ['Super Admin', 'Warehouse Manager'],
    children: [
      { label: 'Routes',       path: '/config/routes'       },
      { label: 'Long Routes',  path: '/config/long-routes'  },
      { label: 'Regimes',      path: '/config/regimes'      },
      { label: 'Destinations', path: '/config/destinations' },
      { label: 'Users',        path: '/config/users'        },
      { label: 'Roles',        path: '/config/roles'        },
      { label: 'Settings',     path: '/config/settings'     },
    ],
  },
];

export default function Sidebar() {
  const { user, hasRole } = useAuth();
  const { collapsed, allocationPoints, distributionPoints, sidebarBadges } = useSidebar();
  const { unreadCount }  = useNotification();
  const location         = useLocation();
  const [expanded, setExpanded] = useState({});

  const toggle = (key) => setExpanded(p => ({ ...p, [key]: !p[key] }));

  const canSee = (roles) => {
    if (!roles || !roles.length) return true;
    if (hasRole('Super Admin')) return true;
    return roles.some(r => hasRole(r));
  };

  const isActive = (path) => location.pathname === path || location.pathname.startsWith(path + '/');

  const getBadge = (badgeKey) => {
    if (badgeKey === 'notifications') return unreadCount;
    return sidebarBadges[badgeKey] || 0;
  };

  return (
    <aside
      className={`flex flex-col flex-shrink-0 transition-all duration-300 h-full overflow-hidden`}
      style={{
        width: collapsed ? 56 : 240,
        background: 'linear-gradient(180deg, #162260 0%, #1E2D7A 40%, #1a2970 100%)',
        borderRight: '1px solid rgba(255,255,255,0.08)',
      }}
    >
      {/* Logo */}
      <div className="flex items-center gap-3 px-3 h-14 flex-shrink-0"
        style={{ borderBottom: '1px solid rgba(255,255,255,0.1)' }}>
        <div className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 text-lg"
          style={{ background: 'rgba(255,255,255,0.12)' }}>
          📡
        </div>
        {!collapsed && (
          <div className="min-w-0">
            <div className="flex items-center gap-0.5">
              <span className="font-extrabold text-sm" style={{ color: '#E31E24' }}>GN</span>
              <span className="font-extrabold text-sm text-white">SW</span>
              <span className="text-white font-medium text-sm ml-1">E-Track</span>
            </div>
            <p className="text-xs truncate" style={{ color: 'rgba(255,255,255,0.45)' }}>Inventory System</p>
          </div>
        )}
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto py-2 scrollbar-thin">
        {NAV_ITEMS.map((item, idx) => {
          if (!canSee(item.roles)) return null;

          // Dynamic children (allocation / distribution points)
          if (item.children === 'allocationPoints' || item.children === 'distributionPoints') {
            const points = item.children === 'allocationPoints' ? allocationPoints : distributionPoints;
            const key    = `dyn_${idx}`;
            const isOpen = expanded[key];
            const active = isActive(item.basePath);
            return (
              <div key={key}>
                <button
                  onClick={() => toggle(key)}
                  className="w-full flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
                  style={{
                    color: active ? 'white' : 'rgba(255,255,255,0.65)',
                    background: active ? 'rgba(255,255,255,0.15)' : 'transparent',
                    borderLeft: active ? '3px solid #E31E24' : '3px solid transparent',
                  }}
                  onMouseEnter={e => { if (!active) e.currentTarget.style.background = 'rgba(255,255,255,0.08)'; }}
                  onMouseLeave={e => { if (!active) e.currentTarget.style.background = 'transparent'; }}
                >
                  <span className="flex-shrink-0 text-base leading-none">{item.icon}</span>
                  {!collapsed && (
                    <>
                      <span className="flex-1 text-left truncate">{item.label}</span>
                      <svg className={`w-3.5 h-3.5 transition-transform flex-shrink-0 ${isOpen ? 'rotate-90' : ''}`}
                        fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                      </svg>
                    </>
                  )}
                </button>
                {!collapsed && isOpen && (
                  <div style={{ background: 'rgba(0,0,0,0.15)' }}>
                    {points.map(ap => {
                      const apPath   = `${item.childPathPrefix || item.basePath}/${ap.id}`;
                      const apActive = isActive(apPath);
                      return (
                        <Link key={ap.id} to={apPath}
                          className="flex items-center justify-between pl-10 pr-3 py-2 text-xs transition-colors"
                          style={{ color: apActive ? 'white' : 'rgba(255,255,255,0.5)' }}
                          onMouseEnter={e => e.currentTarget.style.color = 'white'}
                          onMouseLeave={e => e.currentTarget.style.color = apActive ? 'white' : 'rgba(255,255,255,0.5)'}
                        >
                          <span className="truncate">{ap.name}</span>
                          {ap.received_count > 0 && (
                            <span className="ml-2 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0"
                              style={{ background: '#E31E24' }}>
                              {ap.received_count}
                            </span>
                          )}
                        </Link>
                      );
                    })}
                    {points.length === 0 && (
                      <p className="pl-10 pr-3 py-2 text-xs italic" style={{ color: 'rgba(255,255,255,0.3)' }}>
                        No points found
                      </p>
                    )}
                  </div>
                )}
              </div>
            );
          }

          // Static children (config sub-menu)
          if (Array.isArray(item.children)) {
            const key    = `static_${idx}`;
            const isOpen = expanded[key];
            return (
              <div key={key}>
                <button
                  onClick={() => toggle(key)}
                  className="w-full flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
                  style={{ color: 'rgba(255,255,255,0.65)', background: 'transparent', borderLeft: '3px solid transparent' }}
                  onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.08)'}
                  onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                >
                  <span className="flex-shrink-0 text-base leading-none">{item.icon}</span>
                  {!collapsed && (
                    <>
                      <span className="flex-1 text-left truncate">{item.label}</span>
                      <svg className={`w-3.5 h-3.5 transition-transform flex-shrink-0 ${isOpen ? 'rotate-90' : ''}`}
                        fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                      </svg>
                    </>
                  )}
                </button>
                {!collapsed && isOpen && (
                  <div style={{ background: 'rgba(0,0,0,0.15)' }}>
                    {item.children.map(child => {
                      const ca = isActive(child.path);
                      return (
                        <Link key={child.path} to={child.path}
                          className="flex items-center pl-10 pr-3 py-2 text-xs transition-colors"
                          style={{ color: ca ? 'white' : 'rgba(255,255,255,0.5)' }}
                          onMouseEnter={e => e.currentTarget.style.color = 'white'}
                          onMouseLeave={e => e.currentTarget.style.color = ca ? 'white' : 'rgba(255,255,255,0.5)'}
                        >
                          {child.label}
                        </Link>
                      );
                    })}
                  </div>
                )}
              </div>
            );
          }

          // Simple nav link
          const badge  = item.badge ? getBadge(item.badge) : 0;
          const active = isActive(item.path);
          return (
            <Link key={item.path} to={item.path}
              className="flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
              style={{
                color: active ? 'white' : 'rgba(255,255,255,0.65)',
                background: active ? 'rgba(255,255,255,0.15)' : 'transparent',
                borderLeft: active ? '3px solid #E31E24' : '3px solid transparent',
              }}
              onMouseEnter={e => { if (!active) e.currentTarget.style.background = 'rgba(255,255,255,0.08)'; }}
              onMouseLeave={e => { if (!active) e.currentTarget.style.background = active ? 'rgba(255,255,255,0.15)' : 'transparent'; }}
            >
              <span className="flex-shrink-0 text-base leading-none">{item.icon}</span>
              {!collapsed && (
                <>
                  <span className="flex-1 truncate">{item.label}</span>
                  {badge > 0 && (
                    <span className="text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0"
                      style={{ background: '#E31E24' }}>
                      {badge > 99 ? '99+' : badge}
                    </span>
                  )}
                </>
              )}
            </Link>
          );
        })}
      </nav>

      {/* Footer */}
      {!collapsed && (
        <div className="px-3 py-3 text-xs" style={{ borderTop: '1px solid rgba(255,255,255,0.08)', color: 'rgba(255,255,255,0.4)' }}>
          {user?.roles?.[0] || 'User'}
        </div>
      )}
    </aside>
  );
}
