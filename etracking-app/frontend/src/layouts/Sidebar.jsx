import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useSidebar } from '../contexts/SidebarContext';
import { useNotification } from '../contexts/NotificationContext';

const GN_RED = '#E31E24';

export default function Sidebar() {
  const { user, hasRole } = useAuth();
  const { collapsed, allocationPoints, distributionPoints } = useSidebar();
  const { unreadCount } = useNotification();
  const location = useLocation();
  const [expanded, setExpanded] = useState({});
  const toggle = (key) => setExpanded(p => ({ ...p, [key]: !p[key] }));

  const isSA  = hasRole('Super Admin');
  const isWM  = hasRole('Warehouse Manager');
  const isFO  = hasRole('Finance Officer')           && !isSA && !isWM;
  const isRO  = hasRole('Read Only Tracker Officer')  && !isSA && !isWM;
  const isMon = hasRole('Monitoring Officer')         && !isSA && !isWM;
  const isRet = hasRole('Retrieval Officer')          && !isSA && !isWM;
  const isAff = hasRole('Affixing Officer')           && !isSA && !isWM;
  const isAO  = hasRole('Allocation Officer')         && !isSA && !isWM;
  const isDO  = hasRole('Distribution Officer')       && !isSA && !isWM;
  const isDEO = hasRole('Data Entry Officer')         && !isSA && !isWM;

  const isActive = (path) =>
    location.pathname === path || location.pathname.startsWith(path + '/');

  const renderLink = (path, icon, label, badge = 0) => {
    const active = isActive(path);
    return (
      <Link key={path} to={path}
        className="flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
        style={{
          color: active ? 'white' : 'rgba(255,255,255,0.65)',
          background: active ? 'rgba(255,255,255,0.15)' : 'transparent',
          borderLeft: active ? `3px solid ${GN_RED}` : '3px solid transparent',
        }}
        onMouseEnter={e => { if (!active) e.currentTarget.style.background = 'rgba(255,255,255,0.08)'; }}
        onMouseLeave={e => { if (!active) e.currentTarget.style.background = active ? 'rgba(255,255,255,0.15)' : 'transparent'; }}
      >
        <span className="flex-shrink-0 text-base leading-none">{icon}</span>
        {!collapsed && (
          <>
            <span className="flex-1 truncate">{label}</span>
            {badge > 0 && (
              <span className="text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0"
                style={{ background: GN_RED }}>{badge > 99 ? '99+' : badge}</span>
            )}
          </>
        )}
      </Link>
    );
  };

  const renderGroup = (key, icon, label, children) => {
    const open = expanded[key];
    return (
      <div key={key}>
        <button onClick={() => toggle(key)}
          className="w-full flex items-center gap-3 px-3 py-2.5 text-sm transition-colors"
          style={{ color: 'rgba(255,255,255,0.65)', background: 'transparent', borderLeft: '3px solid transparent' }}
          onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.08)'}
          onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
        >
          <span className="flex-shrink-0 text-base leading-none">{icon}</span>
          {!collapsed && (
            <>
              <span className="flex-1 text-left truncate">{label}</span>
              <svg className={`w-3.5 h-3.5 transition-transform flex-shrink-0 ${open ? 'rotate-90' : ''}`}
                fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
              </svg>
            </>
          )}
        </button>
        {!collapsed && open && (
          <div style={{ background: 'rgba(0,0,0,0.15)' }}>{children}</div>
        )}
      </div>
    );
  };

  const renderSub = (path, label, badge = 0) => {
    const active = isActive(path);
    return (
      <Link key={path} to={path}
        className="flex items-center justify-between pl-10 pr-3 py-2 text-xs transition-colors"
        style={{ color: active ? 'white' : 'rgba(255,255,255,0.5)' }}
        onMouseEnter={e => e.currentTarget.style.color = 'white'}
        onMouseLeave={e => e.currentTarget.style.color = active ? 'white' : 'rgba(255,255,255,0.5)'}
      >
        <span className="truncate">{label}</span>
        {badge > 0 && (
          <span className="ml-2 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold flex-shrink-0"
            style={{ background: GN_RED }}>{badge}</span>
        )}
      </Link>
    );
  };

  const empty = (msg) => (
    <p className="pl-10 py-2 text-xs italic" style={{ color: 'rgba(255,255,255,0.3)' }}>{msg}</p>
  );

  const renderNav = () => {

    /* ── 1. Finance Officer — Finance Management only, no Dashboard ──────── */
    if (isFO && !isRet && !isAff && !isMon && !isAO && !isDO && !isDEO && !isRO) {
      return (
        <>
          {renderGroup('finance', '💰', 'Finance Management', <>
            {renderSub('/invoices',                 'Overstay Receipts')}
            {renderSub('/finance/dispatch-records', 'Dispatch Records')}
            {renderSub('/receipts',                 'Generated Receipts')}
          </>)}
        </>
      );
    }

    /* ── 2. Read Only Tracker — Tracking group only, no Dashboard ─────────── */
    if (isRO) {
      return (
        <>
          {renderGroup('tracking', '🔍', 'Tracking', <>
            {renderSub('/devices',           'Device Tracker')}
            {renderSub('/device-retrievals', 'Device Retrieval')}
            {renderSub('/confirmed-affixed', 'Confirmed Affix')}
          </>)}
        </>
      );
    }

    /* ── 3. Monitoring Officer only ──────────────────────────────────────── */
    if (isMon && !isRet && !isAff && !isAO && !isDO && !isDEO) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('mon-g', '👁️', 'Monitoring', <>
            {renderSub('/monitoring', 'Device Monitoring')}
          </>)}
        </>
      );
    }

    /* ── 4. Dual role: Affixing + Retrieval ──────────────────────────────── */
    if (isAff && isRet) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('ar-g', '🔗', 'Affixing & Retrieval Management', <>
            {renderSub('/confirmed-affixed', 'Confirmed Affixed')}
            {renderSub('/device-retrievals', 'Device Retrievals')}
          </>)}
        </>
      );
    }

    /* ── 5. Retrieval Officer only ───────────────────────────────────────── */
    if (isRet && !isAff) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('ret-g', '🔙', 'Device Retrieval', <>
            {renderSub('/device-retrievals', 'Device Retrievals')}
          </>)}
        </>
      );
    }

    /* ── 6. Affixing Officer only ────────────────────────────────────────── */
    if (isAff && !isRet) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('aff-g', '✅', 'Confirmed Dispatch', <>
            {renderSub('/confirmed-affixed', 'Confirmed Affixed')}
          </>)}
        </>
      );
    }

    /* ── 7. Data Entry Officer ───────────────────────────────────────────── */
    if (isDEO && !isAO && !isDO) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('de-g', '✏️', 'Data Entry/Assignment', <>
            {allocationPoints.length > 0
              ? allocationPoints.map(ap => renderSub(`/data-entry/${ap.id}`, ap.name))
              : empty('No assignments')}
          </>)}
        </>
      );
    }

    /* ── 8. Allocation Officer ───────────────────────────────────────────── */
    if (isAO && !isDO) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('ao-g', '📍', 'Allocation', <>
            {allocationPoints.length > 0
              ? allocationPoints.map(ap => renderSub(`/allocation/${ap.id}`, ap.name, ap.received_count || 0))
              : empty('No allocation points assigned')}
          </>)}
        </>
      );
    }

    /* ── 9. Distribution Officer ─────────────────────────────────────────── */
    if (isDO && !isAO) {
      return (
        <>
          {renderLink('/dashboard', '🏠', 'Dashboard')}
          {renderGroup('do-g', '🌐', 'Distribution Points', <>
            {distributionPoints.length > 0
              ? distributionPoints.map(dp => renderSub(`/distribution/${dp.id}`, dp.name, dp.received_count || 0))
              : empty('No distribution points')}
          </>)}
        </>
      );
    }

    /* ── Super Admin / Warehouse Manager / mixed roles — full menu ───────── */
    const configItems = [
      { label: 'Distribution Points', path: '/config/distribution-points' },
      { label: 'Allocation Points',   path: '/config/allocation-points'   },
      { label: 'Routes',              path: '/config/routes'              },
      { label: 'Long Routes',         path: '/config/long-routes'         },
      { label: 'Regimes',             path: '/config/regimes'             },
      { label: 'Destinations',        path: '/config/destinations'        },
      ...(isSA ? [
        { label: 'Users',    path: '/config/users'    },
        { label: 'Roles',    path: '/config/roles'    },
        { label: 'Settings', path: '/config/settings' },
      ] : []),
    ];

    return (
      <>
        {renderLink('/dashboard', '🏠', 'Dashboard')}

        {renderLink('/devices',     '📱', 'Devices')}
        {renderLink('/stores',      '🏭', 'Inventory')}
        {renderLink('/other-items', '📦', 'Other Items')}
        {renderLink('/transfers',   '🔄', 'Transfers')}

        {renderGroup('dist', '🌐', 'Distribution', <>
          {distributionPoints.length > 0
            ? distributionPoints.map(dp => renderSub(`/distribution/${dp.id}`, dp.name, dp.received_count || 0))
            : empty('No DPs')}
        </>)}

        {renderGroup('alloc', '📍', 'Allocation Points', <>
          {allocationPoints.length > 0
            ? allocationPoints.map(ap => renderSub(`/allocation/${ap.id}`, ap.name, ap.received_count || 0))
            : empty('No APs')}
        </>)}

        {renderGroup('de', '✏️', 'Data Entry', <>
          {allocationPoints.length > 0
            ? allocationPoints.map(ap => renderSub(`/data-entry/${ap.id}`, ap.name))
            : empty('No APs')}
        </>)}

        {renderLink('/confirmed-affixed', '✅', 'Confirmed Dispatch')}
        {renderLink('/device-retrievals', '🔙', 'Device Retrievals')}
        {renderLink('/invoices',          '🧾', 'Overstay Invoices')}
        {renderLink('/monitoring',        '👁️', 'Monitoring')}
        {renderLink('/reports',           '📊', 'Reports')}

        {isSA && renderLink('/notifications', '🔔', 'Notifications', unreadCount)}

        {renderGroup('cfg', '⚙️', 'Configuration', <>
          {configItems.map(c => renderSub(c.path, c.label))}
        </>)}
      </>
    );
  };

  return (
    <aside
      className="flex flex-col flex-shrink-0 transition-all duration-300 h-full overflow-hidden"
      style={{
        width: collapsed ? 56 : 240,
        background: 'linear-gradient(180deg, #162260 0%, #1E2D7A 40%, #1a2970 100%)',
        borderRight: '1px solid rgba(255,255,255,0.08)',
      }}
    >
      <div className="flex items-center gap-3 px-3 h-14 flex-shrink-0"
        style={{ borderBottom: '1px solid rgba(255,255,255,0.1)' }}>
        <div className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 text-lg"
          style={{ background: 'rgba(255,255,255,0.12)' }}>
          📡
        </div>
        {!collapsed && (
          <div className="min-w-0">
            <div className="flex items-center gap-0.5">
              <span className="font-extrabold text-sm" style={{ color: GN_RED }}>GN</span>
              <span className="font-extrabold text-sm text-white">SW</span>
              <span className="text-white font-medium text-sm ml-1">E-Track</span>
            </div>
            <p className="text-xs truncate" style={{ color: 'rgba(255,255,255,0.45)' }}>Inventory System</p>
          </div>
        )}
      </div>

      <nav className="flex-1 overflow-y-auto py-2 scrollbar-thin">
        {renderNav()}
      </nav>

      {!collapsed && (
        <div className="px-3 py-3 text-xs"
          style={{ borderTop: '1px solid rgba(255,255,255,0.08)', color: 'rgba(255,255,255,0.4)' }}>
          {user?.roles?.[0] || 'User'}
        </div>
      )}
    </aside>
  );
}
