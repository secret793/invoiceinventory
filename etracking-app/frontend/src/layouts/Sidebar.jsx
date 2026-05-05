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
  const { user, hasRole, hasPermission } = useAuth();
  const { collapsed, allocationPoints, distributionPoints, sidebarBadges } = useSidebar();
  const { unreadCount }  = useNotification();
  const location         = useLocation();
  const [expanded, setExpanded] = useState({});

  const toggle = (key) => setExpanded(p => ({ ...p, [key]: !p[key] }));

  const canSee = (roles) => {
    if (!roles || !roles.length) return true;
    if (hasRole('Super Admin'))  return true;
    return roles.some(r => hasRole(r));
  };

  const isActive = (path) => location.pathname === path || location.pathname.startsWith(path + '/');

  const getBadge = (badgeKey) => {
    if (badgeKey === 'notifications') return unreadCount;
    return sidebarBadges[badgeKey] || 0;
  };

  return (
    <aside className={`bg-gray-900 text-white flex flex-col flex-shrink-0 transition-all duration-300
      ${collapsed ? 'w-14' : 'w-64'} h-full overflow-hidden`}>
      {/* Logo */}
      <div className="flex items-center gap-3 px-4 h-14 border-b border-gray-800 flex-shrink-0">
        <span className="text-2xl flex-shrink-0">📡</span>
        {!collapsed && (
          <div className="min-w-0">
            <p className="font-bold text-sm leading-tight truncate">GNSW E-Track</p>
            <p className="text-gray-400 text-xs truncate">Inventory System</p>
          </div>
        )}
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto py-3 scrollbar-thin">
        {NAV_ITEMS.map((item, idx) => {
          if (!canSee(item.roles)) return null;

          // Items with dynamic children (allocation/distribution points)
          if (item.children === 'allocationPoints' || item.children === 'distributionPoints') {
            const points = item.children === 'allocationPoints' ? allocationPoints : distributionPoints;
            const key    = `dyn_${idx}`;
            const isOpen = expanded[key];
            return (
              <div key={key}>
                <button onClick={() => toggle(key)}
                  className={`w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                    ${isActive(item.basePath) ? 'bg-blue-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'}`}>
                  <span className="flex-shrink-0 text-base">{item.icon}</span>
                  {!collapsed && (
                    <>
                      <span className="flex-1 text-left truncate">{item.label}</span>
                      <svg className={`w-4 h-4 transition-transform ${isOpen ? 'rotate-90' : ''}`}
                        fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                      </svg>
                    </>
                  )}
                </button>
                {!collapsed && isOpen && (
                  <div className="bg-gray-950/50">
                    {points.map(ap => {
                      const apPath = `${item.childPathPrefix || item.basePath}/${ap.id}`;
                      return (
                        <Link key={ap.id} to={apPath}
                          className={`flex items-center justify-between pl-10 pr-4 py-2 text-xs transition-colors
                            ${isActive(apPath) ? 'text-blue-400 bg-blue-900/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'}`}>
                          <span className="truncate">{ap.name}</span>
                          {ap.received_count > 0 && (
                            <span className="ml-2 bg-blue-600 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0">
                              {ap.received_count}
                            </span>
                          )}
                        </Link>
                      );
                    })}
                    {points.length === 0 && (
                      <p className="pl-10 pr-4 py-2 text-xs text-gray-600 italic">No points found</p>
                    )}
                  </div>
                )}
              </div>
            );
          }

          // Items with static children (config sub-menu)
          if (Array.isArray(item.children)) {
            const key    = `static_${idx}`;
            const isOpen = expanded[key];
            return (
              <div key={key}>
                <button onClick={() => toggle(key)}
                  className={`w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                    text-gray-300 hover:bg-gray-800 hover:text-white`}>
                  <span className="flex-shrink-0 text-base">{item.icon}</span>
                  {!collapsed && (
                    <>
                      <span className="flex-1 text-left truncate">{item.label}</span>
                      <svg className={`w-4 h-4 transition-transform ${isOpen ? 'rotate-90' : ''}`}
                        fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                      </svg>
                    </>
                  )}
                </button>
                {!collapsed && isOpen && (
                  <div className="bg-gray-950/50">
                    {item.children.map(child => (
                      <Link key={child.path} to={child.path}
                        className={`flex items-center pl-10 pr-4 py-2 text-xs transition-colors
                          ${isActive(child.path) ? 'text-blue-400 bg-blue-900/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white'}`}>
                        {child.label}
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            );
          }

          // Simple link
          const badge = item.badge ? getBadge(item.badge) : 0;
          return (
            <Link key={item.path} to={item.path}
              className={`flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
                ${isActive(item.path) ? 'bg-blue-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'}`}>
              <span className="flex-shrink-0 text-base">{item.icon}</span>
              {!collapsed && (
                <>
                  <span className="flex-1 truncate">{item.label}</span>
                  {badge > 0 && (
                    <span className="bg-red-500 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0">
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
        <div className="px-4 py-3 border-t border-gray-800 text-xs text-gray-500">
          {user?.roles?.[0] || 'User'}
        </div>
      )}
    </aside>
  );
}
